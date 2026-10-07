<?php

namespace App\Console\Commands;

use App\Models\EmailCampaign;
use App\Services\EmailCampaignService;
use Illuminate\Console\Command;

class RefreshEmailCampaignStats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email-campaign:refresh-stats
                            {--campaign-id= : Specific campaign ID to refresh}
                            {--active-only : Only refresh active campaigns}
                            {--all : Refresh all campaigns}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh email campaign statistics from recipient data';

    /**
     * Execute the console command.
     */
    public function handle(EmailCampaignService $emailService): int
    {
        $campaignId = $this->option('campaign-id');
        $activeOnly = $this->option('active-only');
        $all = $this->option('all');

        if ($campaignId) {
            // Refresh specific campaign
            $this->info("Refreshing campaign {$campaignId}...");
            $success = $emailService->syncCampaignStats($campaignId);

            if ($success) {
                $stats = $emailService->refreshCampaignStats($campaignId);
                $this->info("✅ Campaign {$campaignId} refreshed: {$stats['sent_count']} sent, {$stats['failed_count']} failed, {$stats['progress']}% progress");
            } else {
                $this->error("❌ Failed to refresh campaign {$campaignId}");

                return 1;
            }
        } elseif ($activeOnly || (! $campaignId && ! $all)) {
            // Refresh only active campaigns (default behavior)
            $campaigns = EmailCampaign::whereIn('status', ['pending', 'sending'])->get();
            $this->info("Refreshing {$campaigns->count()} active campaigns...");

            $refreshed = 0;
            foreach ($campaigns as $campaign) {
                if ($emailService->syncCampaignStats($campaign->id)) {
                    $refreshed++;
                    $stats = $emailService->refreshCampaignStats($campaign->id);
                    $this->line("✅ Campaign {$campaign->id}: {$stats['sent_count']} sent, {$stats['progress']}% progress");
                }
            }

            $this->info("Refreshed {$refreshed}/{$campaigns->count()} campaigns");
        } elseif ($all) {
            // Refresh all campaigns
            $campaigns = EmailCampaign::all();
            $this->info("Refreshing all {$campaigns->count()} campaigns...");

            $refreshed = 0;
            $bar = $this->output->createProgressBar($campaigns->count());
            $bar->start();

            foreach ($campaigns as $campaign) {
                if ($emailService->syncCampaignStats($campaign->id)) {
                    $refreshed++;
                }
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
            $this->info("Refreshed {$refreshed}/{$campaigns->count()} campaigns");
        }

        return 0;
    }
}
