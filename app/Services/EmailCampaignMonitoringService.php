<?php

namespace App\Services;

use App\Models\EmailCampaign;
use App\Models\EmailCampaignRecipient;
use Illuminate\Support\Facades\Log;

class EmailCampaignMonitoringService
{
    /**
     * Monitor campaign health and performance
     */
    public function monitorCampaign(int $campaignId): array
    {
        $campaign = EmailCampaign::find($campaignId);
        if (! $campaign) {
            return ['error' => 'Campaign not found'];
        }

        $stats = $this->getCampaignStats($campaignId);
        $health = $this->assessCampaignHealth($stats);

        // Log critical issues
        if ($health['status'] === 'critical') {
            Log::critical('Email campaign critical issues detected', [
                'campaign_id' => $campaignId,
                'issues' => $health['issues'],
                'stats' => $stats,
            ]);
        }

        return [
            'campaign' => $campaign,
            'stats' => $stats,
            'health' => $health,
            'performance_metrics' => $this->getPerformanceMetrics($campaignId),
        ];
    }

    /**
     * Get comprehensive campaign statistics (direct database query)
     */
    private function getCampaignStats(int $campaignId): array
    {
        $stats = EmailCampaignRecipient::where('email_campaign_id', $campaignId)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = "sending" THEN 1 ELSE 0 END) as sending,
                SUM(CASE WHEN status = "sent" THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed,
                MIN(created_at) as started_at,
                MAX(updated_at) as last_activity
            ')->first();

        return [
            'total' => (int) $stats->total,
            'pending' => (int) $stats->pending,
            'sending' => (int) $stats->sending,
            'sent' => (int) $stats->sent,
            'failed' => (int) $stats->failed,
            'success_rate' => $stats->total > 0 ? round(($stats->sent / $stats->total) * 100, 2) : 0,
            'failure_rate' => $stats->total > 0 ? round(($stats->failed / $stats->total) * 100, 2) : 0,
            'completion_rate' => $stats->total > 0 ? round((($stats->sent + $stats->failed) / $stats->total) * 100, 2) : 0,
            'started_at' => $stats->started_at,
            'last_activity' => $stats->last_activity,
        ];
    }

    /**
     * Assess campaign health status
     */
    private function assessCampaignHealth(array $stats): array
    {
        $issues = [];
        $status = 'healthy';

        // Check failure rate
        if ($stats['failure_rate'] > 10) {
            $issues[] = "High failure rate: {$stats['failure_rate']}%";
            $status = $stats['failure_rate'] > 25 ? 'critical' : 'warning';
        }

        // Check for stalled campaigns
        if ($stats['last_activity'] && now()->diffInMinutes($stats['last_activity']) > 30 && $stats['pending'] > 0) {
            $issues[] = "Campaign appears stalled - no activity for 30+ minutes with {$stats['pending']} pending";
            $status = 'warning';
        }

        // Check for stuck sending status
        if ($stats['sending'] > 50) {
            $issues[] = "Many emails stuck in 'sending' status: {$stats['sending']}";
            $status = 'warning';
        }

        return [
            'status' => $status,
            'issues' => $issues,
            'recommendations' => $this->getRecommendations($stats, $issues),
        ];
    }

    /**
     * Get performance metrics for optimization
     */
    private function getPerformanceMetrics(int $campaignId): array
    {
        $campaign = EmailCampaign::find($campaignId);
        if (! $campaign || ! $campaign->created_at) {
            return [];
        }

        $stats = $this->getCampaignStats($campaignId);
        $elapsedMinutes = now()->diffInMinutes($campaign->created_at);

        return [
            'elapsed_minutes' => $elapsedMinutes,
            'emails_per_minute' => $elapsedMinutes > 0 ? round($stats['sent'] / $elapsedMinutes, 2) : 0,
            'estimated_completion' => $this->estimateCompletion($stats, $elapsedMinutes),
            'throughput_analysis' => $this->analyzeThroughput($stats, $elapsedMinutes),
        ];
    }

    /**
     * Estimate campaign completion time
     */
    private function estimateCompletion(array $stats, int $elapsedMinutes): ?string
    {
        if ($stats['completion_rate'] >= 100) {
            return 'Completed';
        }

        if ($elapsedMinutes > 0 && $stats['sent'] > 0) {
            $emailsPerMinute = $stats['sent'] / $elapsedMinutes;
            if ($emailsPerMinute > 0) {
                $remainingEmails = $stats['total'] - $stats['sent'] - $stats['failed'];
                $estimatedMinutes = $remainingEmails / $emailsPerMinute;

                return now()->addMinutes($estimatedMinutes)->format('Y-m-d H:i:s');
            }
        }

        return null;
    }

    /**
     * Analyze throughput performance
     */
    private function analyzeThroughput(array $stats, int $elapsedMinutes): array
    {
        $emailsPerMinute = $elapsedMinutes > 0 ? $stats['sent'] / $elapsedMinutes : 0;

        $analysis = [
            'current_rate' => round($emailsPerMinute, 2),
            'performance' => 'unknown',
        ];

        // Performance benchmarks (emails per minute)
        if ($emailsPerMinute >= 50) {
            $analysis['performance'] = 'excellent';
        } elseif ($emailsPerMinute >= 25) {
            $analysis['performance'] = 'good';
        } elseif ($emailsPerMinute >= 10) {
            $analysis['performance'] = 'fair';
        } elseif ($emailsPerMinute > 0) {
            $analysis['performance'] = 'poor';
        }

        return $analysis;
    }

    /**
     * Get optimization recommendations
     */
    private function getRecommendations(array $stats, array $issues): array
    {
        $recommendations = [];

        if ($stats['failure_rate'] > 10) {
            $recommendations[] = 'Consider reducing batch size or increasing delays between batches';
            $recommendations[] = 'Check email provider limits and authentication';
            $recommendations[] = 'Review email content for spam-like characteristics';
        }

        if (str_contains(implode(' ', $issues), 'stalled')) {
            $recommendations[] = 'Check queue workers are running';
            $recommendations[] = 'Verify Redis/database connectivity';
            $recommendations[] = 'Consider restarting stuck jobs';
        }

        if ($stats['sending'] > 50) {
            $recommendations[] = 'Check for hung processes or worker timeouts';
            $recommendations[] = 'Consider increasing job timeout settings';
        }

        return $recommendations;
    }

    /**
     * Get campaign performance summary for dashboard
     */
    public function getCampaignSummary(int $campaignId): array
    {
        $monitoring = $this->monitorCampaign($campaignId);

        return [
            'id' => $campaignId,
            'status' => $monitoring['campaign']->status ?? 'unknown',
            'progress' => $monitoring['campaign']->progress ?? 0,
            'health' => $monitoring['health']['status'],
            'sent' => $monitoring['stats']['sent'],
            'failed' => $monitoring['stats']['failed'],
            'total' => $monitoring['stats']['total'],
            'success_rate' => $monitoring['stats']['success_rate'],
            'emails_per_minute' => $monitoring['performance_metrics']['emails_per_minute'] ?? 0,
            'issues' => count($monitoring['health']['issues']),
        ];
    }
}
