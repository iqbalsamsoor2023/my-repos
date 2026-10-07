<?php

namespace App\Console\Commands;

use App\Models\EmailCampaign;
use App\Services\EmailCampaignMonitoringService;
use Illuminate\Console\Command;

class MonitorEmailCampaigns extends Command
{
    protected $signature = 'email-campaign:monitor {--campaign-id= : Specific campaign ID to monitor} {--active-only : Only monitor active campaigns}';

    protected $description = 'Monitor email campaign performance and health';

    public function handle(EmailCampaignMonitoringService $monitoringService): int
    {
        $this->info('🔍 Email Campaign Monitor Starting...');

        $campaignId = $this->option('campaign-id');
        $activeOnly = $this->option('active-only');

        if ($campaignId) {
            return $this->monitorSpecificCampaign($monitoringService, (int) $campaignId);
        }

        return $this->monitorMultipleCampaigns($monitoringService, $activeOnly);
    }

    private function monitorSpecificCampaign(EmailCampaignMonitoringService $service, int $campaignId): int
    {
        $this->info("Monitoring Campaign #{$campaignId}");

        $result = $service->monitorCampaign($campaignId);

        if (isset($result['error'])) {
            $this->error($result['error']);

            return 1;
        }

        $this->displayCampaignDetails($result);

        return 0;
    }

    private function monitorMultipleCampaigns(EmailCampaignMonitoringService $service, bool $activeOnly): int
    {
        $query = EmailCampaign::query();

        if ($activeOnly) {
            $query->whereIn('status', ['pending', 'sending']);
        } else {
            $query->whereIn('status', ['pending', 'sending', 'completed'])
                ->where('created_at', '>=', now()->subDays(7)); // Last 7 days
        }

        $campaigns = $query->orderBy('created_at', 'desc')->get();

        if ($campaigns->isEmpty()) {
            $this->info('No campaigns to monitor.');

            return 0;
        }

        $this->info("Found {$campaigns->count()} campaigns to monitor");

        $headers = ['ID', 'Subject', 'Status', 'Progress', 'Health', 'Sent', 'Failed', 'Rate/min', 'Issues'];
        $rows = [];

        foreach ($campaigns as $campaign) {
            $summary = $service->getCampaignSummary($campaign->id);

            $rows[] = [
                $campaign->id,
                substr($campaign->subject, 0, 30).'...',
                $this->colorStatus($summary['status']),
                $summary['progress'].'%',
                $this->colorHealth($summary['health']),
                number_format($summary['sent']),
                $summary['failed'] > 0 ? "<fg=red>{$summary['failed']}</>" : $summary['failed'],
                number_format($summary['emails_per_minute'], 1),
                $summary['issues'] > 0 ? "<fg=yellow>{$summary['issues']}</>" : '0',
            ];
        }

        $this->table($headers, $rows);

        // Show detailed issues for problematic campaigns
        foreach ($campaigns as $campaign) {
            $result = $service->monitorCampaign($campaign->id);
            if ($result['health']['status'] !== 'healthy') {
                $this->warn("\n⚠️  Campaign #{$campaign->id} Issues:");
                foreach ($result['health']['issues'] as $issue) {
                    $this->line("   • {$issue}");
                }
                if (! empty($result['health']['recommendations'])) {
                    $this->info('   Recommendations:');
                    foreach ($result['health']['recommendations'] as $recommendation) {
                        $this->line("   → {$recommendation}");
                    }
                }
            }
        }

        return 0;
    }

    private function displayCampaignDetails(array $result): void
    {
        $campaign = $result['campaign'];
        $stats = $result['stats'];
        $health = $result['health'];
        $performance = $result['performance_metrics'];

        $this->info("📧 Campaign: {$campaign->subject}");
        $this->line("Status: {$this->colorStatus($campaign->status)}");
        $this->line("Progress: {$campaign->progress}%");

        $this->newLine();
        $this->info('📊 Statistics:');
        $this->line('Total Recipients: '.number_format($stats['total']));
        $this->line('Sent: <fg=green>'.number_format($stats['sent']).'</>');
        $this->line('Failed: <fg=red>'.number_format($stats['failed']).'</>');
        $this->line('Pending: <fg=yellow>'.number_format($stats['pending']).'</>');
        $this->line("Success Rate: {$stats['success_rate']}%");

        $this->newLine();
        $this->info('⚡ Performance:');
        $this->line("Emails per minute: {$performance['emails_per_minute']}");
        $this->line("Throughput: {$performance['throughput_analysis']['performance']}");
        if (isset($performance['estimated_completion'])) {
            $this->line("Estimated completion: {$performance['estimated_completion']}");
        }

        $this->newLine();
        $this->info("🏥 Health: {$this->colorHealth($health['status'])}");

        if (! empty($health['issues'])) {
            $this->warn('Issues found:');
            foreach ($health['issues'] as $issue) {
                $this->line("   • {$issue}");
            }
        }

        if (! empty($health['recommendations'])) {
            $this->info('Recommendations:');
            foreach ($health['recommendations'] as $recommendation) {
                $this->line("   → {$recommendation}");
            }
        }
    }

    private function colorStatus(string $status): string
    {
        return match ($status) {
            'completed' => "<fg=green>{$status}</>",
            'sending' => "<fg=blue>{$status}</>",
            'pending' => "<fg=yellow>{$status}</>",
            'failed' => "<fg=red>{$status}</>",
            default => $status
        };
    }

    private function colorHealth(string $health): string
    {
        return match ($health) {
            'healthy' => "<fg=green>{$health}</>",
            'warning' => "<fg=yellow>{$health}</>",
            'critical' => "<fg=red>{$health}</>",
            default => $health
        };
    }
}
