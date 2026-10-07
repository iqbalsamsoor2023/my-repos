<?php

namespace App\Services;

use Exception;
use App\Jobs\DevOps\SendBlastEmailBatchJob;
use App\Mail\BlastEmail;
use App\Models\EmailCampaign;
use App\Models\EmailCampaignRecipient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailCampaignService
{
    public function sendTestEmail(EmailCampaign $campaign, string $email): void
    {
        // Validate image before sending test email
        $this->validateCampaignImage($campaign);

        Mail::to($email)->send(new BlastEmail($campaign->subject, $campaign->message, $campaign));
    }

    public function queueBlast(EmailCampaign $campaign): void
    {
        // Validate image accessibility before proceeding
        $this->validateCampaignImage($campaign);

        $campaign->update([
            'status' => 'pending',
            'sent_count' => 0,
            'failed_count' => 0,
            'progress' => 0,
            'last_error' => null,
        ]);

        $baseQuery = $campaign->getTargetUsersQuery()->distinct();
        $totalRecipients = $baseQuery->count();
        $campaign->update(['total_recipients' => $totalRecipients]);

        if ($totalRecipients == 0) {
            $campaign->update([
                'status' => 'completed',
                'progress' => 100,
                'sent_count' => 0,
                'failed_count' => 0,
            ]);

            return;
        }

        $campaign->markAsSending();
        $batchNumber = 0;
        $batchSize = 100;

        try {
            $baseQuery->chunkById($batchSize, function ($userChunk) use ($campaign, &$batchNumber) {
                $recipients = [];
                $now = now();

                foreach ($userChunk as $user) {
                    $recipients[] = [
                        'email_campaign_id' => $campaign->id,
                        'email' => $user->email,
                        'status' => 'pending',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if (! empty($recipients)) {
                    DB::transaction(fn () => EmailCampaignRecipient::insertOrIgnore($recipients));
                }

                $emails = collect($userChunk)->pluck('email')->toArray();
                $recipientIds = EmailCampaignRecipient::where('email_campaign_id', $campaign->id)
                    ->whereIn('email', $emails)
                    ->where('status', 'pending')
                    ->orderBy('id')
                    ->pluck('id')
                    ->toArray();

                if (! empty($recipientIds)) {
                    // SendGrid optimized: 5 second delay between batches (faster than 10s)
                    // With 100ms per email, each batch takes ~10 seconds
                    // 5 second delay gives enough buffer without slowing down unnecessarily
                    $delay = now()->addSeconds($batchNumber * 5);

                    SendBlastEmailBatchJob::dispatch(
                        $recipientIds,
                        $campaign->id,
                        $campaign->subject,
                        $campaign->message
                    )
                        ->onQueue('EmailBlastQueue')
                        ->onConnection('database')
                        ->delay($delay);

                    $batchNumber++;
                }
            }, 'id');

        } catch (Exception $e) {
            Log::channel('email_blast')->error('Failed to process email campaign', [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage(),
            ]);

            $campaign->update(['status' => 'failed']);
            throw $e;
        }
    }

    public function updateCampaignProgress(int $campaignId): void
    {
        $campaign = EmailCampaign::select('id', 'status', 'progress', 'sent_count', 'failed_count', 'updated_at')
            ->find($campaignId);

        if (! $campaign) {
            return;
        }

        try {
            $counts = EmailCampaignRecipient::where('email_campaign_id', $campaignId)
                ->selectRaw('
                    COUNT(*) as total,
                    SUM(CASE WHEN status = "sent" THEN 1 ELSE 0 END) as sent,
                    SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed
                ')
                ->first();

            if (! $counts || $counts->total == 0) {
                return;
            }

            $completed = (int) $counts->sent + (int) $counts->failed;
            $progress = $counts->total > 0 ? round(($completed / $counts->total) * 100, 2) : 0;

            $newStatus = $this->determineCampaignStatus(
                $campaign->status,
                (int) $counts->sent,
                (int) $counts->failed,
                (int) $counts->total
            );

            $statusChanged = $newStatus !== $campaign->status;
            $progressChanged = abs($progress - (float) $campaign->progress) >= 0.5;
            $countChanged = $campaign->sent_count !== (int) $counts->sent
                || $campaign->failed_count !== (int) $counts->failed;

            $recentUpdate = $campaign->updated_at
                ? now()->diffInSeconds($campaign->updated_at) < 30
                : false;

            if ($recentUpdate && ! $statusChanged && ! $progressChanged && ! $countChanged) {
                return;
            }

            $updateData = [
                'progress' => $progress,
                'sent_count' => (int) $counts->sent,
                'failed_count' => (int) $counts->failed,
                'updated_at' => now(),
            ];

            if ($statusChanged) {
                $updateData['status'] = $newStatus;
            }

            EmailCampaign::where('id', $campaignId)->update($updateData);

            if ($statusChanged && in_array($newStatus, ['completed', 'partial', 'failed'], true)) {
                Log::channel('email_blast')->info('Email campaign completed', [
                    'campaign_id' => $campaignId,
                    'status' => $newStatus,
                    'total_sent' => (int) $counts->sent,
                    'total_failed' => (int) $counts->failed,
                ]);
            }

        } catch (Exception $e) {
            Log::channel('email_blast')->error('Failed to update campaign progress', [
                'campaign_id' => $campaignId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function determineCampaignStatus(string $currentStatus, int $sent, int $failed, int $total): string
    {
        $completed = $sent + $failed >= $total && $total > 0;

        if (! $completed) {
            if ($sent === 0 && $failed === 0) {
                return $currentStatus;
            }

            return 'sending';
        }

        if ($failed === $total) {
            return 'failed';
        }

        if ($failed > 0) {
            return 'partial';
        }

        return 'completed';
    }

    /**
     * Validate that the campaign image is accessible.
     */
    private function validateCampaignImage(EmailCampaign $campaign): void
    {
        $imageUrl = $campaign->imageUrl;

        // Skip validation if no image or using fallback image
        if (! $imageUrl || $imageUrl === 'https://dashboard.mymooban.co.th/images/no-image.png') {
            return;
        }

        try {
            $response = Http::timeout(2)->head($imageUrl);

            if (! $response->successful()) {
                Log::channel('email_blast')->warning('Campaign image not accessible', [
                    'campaign_id' => $campaign->id,
                    'image_url' => $imageUrl,
                    'status_code' => $response->status(),
                ]);
            }

        } catch (Exception $e) {
            // Silently continue - template handles missing images gracefully
        }
    }

    /**
     * Check if an image URL is accessible for email delivery.
     */
    public function isImageAccessible(string $imageUrl): bool
    {
        if (empty($imageUrl) || $imageUrl === 'https://dashboard.mymooban.co.th/images/no-image.png') {
            return false;
        }

        try {
            $response = Http::timeout(2)->head($imageUrl);
            $accessible = $response->successful() &&
                         $response->header('content-type') &&
                         str_starts_with($response->header('content-type'), 'image/');

            return $accessible;

        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Refresh campaign statistics for dashboard display
     * Simple approach: only refresh if counts are empty or campaign is active
     *
     * @return array Campaign statistics
     */
    public function refreshCampaignStats(int $campaignId): array
    {
        $campaign = EmailCampaign::find($campaignId);

        if (! $campaign) {
            return [
                'total_recipients' => 0,
                'sent_count' => 0,
                'failed_count' => 0,
                'pending_count' => 0,
                'progress' => 0,
                'status' => 'not_found',
                'success_rate' => 0,
                'is_active' => false,
                'needs_refresh' => false,
            ];
        }

        // Check if we need to refresh counts
        $needsRefresh = $this->shouldRefreshCampaignStats($campaign);

        if (! $needsRefresh) {
            // Return existing data from campaign table
            $completed = $campaign->sent_count + $campaign->failed_count;
            $progress = $campaign->total_recipients > 0
                ? round(($completed / $campaign->total_recipients) * 100, 2)
                : 0;
            $successRate = $completed > 0
                ? round(($campaign->sent_count / $completed) * 100, 2)
                : 0;

            return [
                'total_recipients' => $campaign->total_recipients ?? 0,
                'sent_count' => $campaign->sent_count ?? 0,
                'failed_count' => $campaign->failed_count ?? 0,
                'pending_count' => max(0, ($campaign->total_recipients ?? 0) - $completed),
                'progress' => $progress,
                'status' => $campaign->status,
                'success_rate' => $successRate,
                'is_active' => in_array($campaign->status, ['pending', 'sending']),
                'needs_refresh' => false,
            ];
        }

        // Get real-time counts from recipients table
        $counts = EmailCampaignRecipient::where('email_campaign_id', $campaignId)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = "sent" THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending
            ')
            ->first();

        if (! $counts || $counts->total == 0) {
            return [
                'total_recipients' => $campaign->total_recipients ?? 0,
                'sent_count' => 0,
                'failed_count' => 0,
                'pending_count' => 0,
                'progress' => 0,
                'status' => $campaign->status,
                'success_rate' => 0,
                'is_active' => false,
                'needs_refresh' => true,
            ];
        }

        $totalRecipients = (int) $counts->total;
        $sentCount = (int) $counts->sent;
        $failedCount = (int) $counts->failed;
        $pendingCount = (int) $counts->pending;

        $completed = $sentCount + $failedCount;
        $progress = $totalRecipients > 0 ? round(($completed / $totalRecipients) * 100, 2) : 0;
        $successRate = $completed > 0 ? round(($sentCount / $completed) * 100, 2) : 0;

        // Determine current status
        $status = $this->determineCampaignStatus(
            $campaign->status,
            $sentCount,
            $failedCount,
            $totalRecipients
        );

        $isActive = in_array($status, ['pending', 'sending']);

        return [
            'total_recipients' => $totalRecipients,
            'sent_count' => $sentCount,
            'failed_count' => $failedCount,
            'pending_count' => $pendingCount,
            'progress' => $progress,
            'status' => $status,
            'success_rate' => $successRate,
            'is_active' => $isActive,
            'needs_refresh' => true,
        ];
    }

    /**
     * Determine if campaign stats need refreshing
     * Simple logic: refresh if counts are empty OR campaign is active
     */
    private function shouldRefreshCampaignStats(EmailCampaign $campaign): bool
    {
        // Always refresh if counts are empty (0 or null)
        if (($campaign->sent_count ?? 0) == 0 && ($campaign->failed_count ?? 0) == 0) {
            return true;
        }

        // Always refresh if campaign is currently active
        if (in_array($campaign->status, ['pending', 'sending'])) {
            return true;
        }

        // Don't refresh if campaign is completed and has counts
        return false;
    }

    /**
     * Update campaign record with fresh statistics (called from dashboard)
     * Only updates if campaign needs refreshing
     */
    public function syncCampaignStats(int $campaignId): bool
    {
        $campaign = EmailCampaign::find($campaignId);

        if (! $campaign) {
            return false;
        }

        if (! $this->shouldRefreshCampaignStats($campaign)) {
            return true;
        }

        $stats = $this->refreshCampaignStats($campaignId);

        if ($stats['status'] === 'not_found') {
            return false;
        }

        if ($stats['needs_refresh']) {
            $updated = EmailCampaign::where('id', $campaignId)->update([
                'total_recipients' => $stats['total_recipients'],
                'sent_count' => $stats['sent_count'],
                'failed_count' => $stats['failed_count'],
                'progress' => $stats['progress'],
                'status' => $stats['status'],
                'updated_at' => now(),
            ]);

            return $updated > 0;
        }

        return true;
    }

    /**
     * Smart resend: only resend failed emails, skip successful ones
     */
    public function resendFailedEmails(EmailCampaign $campaign): void
    {
        // Validate image before proceeding
        $this->validateCampaignImage($campaign);

        $failedCount = EmailCampaignRecipient::where('email_campaign_id', $campaign->id)
            ->where('status', 'failed')
            ->count();

        if ($failedCount === 0) {
            return;
        }

        // Reset failed recipients to pending for retry
        EmailCampaignRecipient::where('email_campaign_id', $campaign->id)
            ->where('status', 'failed')
            ->update([
                'status' => 'pending',
                'updated_at' => now(),
            ]);

        $campaign->update([
            'status' => 'sending',
            'last_error' => null,
        ]);

        // Process failed emails in batches (smaller batch for retries)
        $batchNumber = 0;
        $batchSize = 50;

        EmailCampaignRecipient::where('email_campaign_id', $campaign->id)
            ->where('status', 'pending')
            ->chunkById($batchSize, function ($recipients) use ($campaign, &$batchNumber) {
                $recipientIds = $recipients->pluck('id')->toArray();

                if (! empty($recipientIds)) {
                    // SendGrid optimized: 7 second delay for retry batches
                    // Smaller batches (50) with slightly longer delay to avoid issues
                    $delay = now()->addSeconds($batchNumber * 7);

                    SendBlastEmailBatchJob::dispatch(
                        $recipientIds,
                        $campaign->id,
                        $campaign->subject,
                        $campaign->message
                    )
                        ->onQueue('EmailBlastQueue')
                        ->onConnection('database')
                        ->delay($delay);

                    $batchNumber++;
                }
            }, 'id');
    }

    /**
     * Check if campaign has any failed emails that can be resent
     * Simple direct query - no external cache needed
     */
    public function hasFailedEmails(int $campaignId): bool
    {
        // Simple direct query each time - fast enough for button visibility
        return EmailCampaignRecipient::where('email_campaign_id', $campaignId)
            ->where('status', 'failed')
            ->exists();
    }

    /**
     * Check if campaign is completely successful
     * Simple direct query - no external cache needed
     */
    public function isCompletelySuccessful(int $campaignId): bool
    {
        // Simple direct query each time
        $stats = EmailCampaignRecipient::where('email_campaign_id', $campaignId)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = "sent" THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending
            ')
            ->first();

        if (! $stats || $stats->total == 0) {
            return false;
        }

        // Campaign is completely successful if all emails are sent (no failed or pending)
        return $stats->sent == $stats->total && $stats->failed == 0 && $stats->pending == 0;
    }

    /**
     * Batch sync multiple campaigns efficiently
     * Reduces N+1 queries by processing multiple campaigns at once
     */
    public function batchSyncCampaignStats(array $campaignIds): void
    {
        if (empty($campaignIds)) {
            return;
        }

        $campaigns = EmailCampaign::whereIn('id', $campaignIds)
            ->get()
            ->keyBy('id');

        $campaignsToRefresh = $campaigns->filter(function ($campaign) {
            return $this->shouldRefreshCampaignStats($campaign);
        });

        if ($campaignsToRefresh->isEmpty()) {
            return;
        }

        $allStats = EmailCampaignRecipient::whereIn('email_campaign_id', $campaignsToRefresh->keys())
            ->selectRaw('
                email_campaign_id,
                COUNT(*) as total,
                SUM(CASE WHEN status = "sent" THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed
            ')
            ->groupBy('email_campaign_id')
            ->get()
            ->keyBy('email_campaign_id');

        $updateData = [];
        foreach ($campaignsToRefresh as $campaignId => $campaign) {
            $stats = $allStats->get($campaignId);

            if ($stats) {
                $completed = $stats->sent + $stats->failed;
                $progress = $stats->total > 0 ? round(($completed / $stats->total) * 100, 2) : 0;
                $newStatus = $this->determineCampaignStatus(
                    $campaign->status,
                    $stats->sent,
                    $stats->failed,
                    $stats->total
                );

                $updateData[$campaignId] = [
                    'total_recipients' => $stats->total,
                    'sent_count' => $stats->sent,
                    'failed_count' => $stats->failed,
                    'progress' => $progress,
                    'status' => $newStatus,
                    'updated_at' => now(),
                ];
            }
        }

        if (! empty($updateData)) {
            DB::transaction(function () use ($updateData) {
                foreach ($updateData as $campaignId => $data) {
                    EmailCampaign::where('id', $campaignId)->update($data);
                }
            });
        }
    }

    /**
     * Get campaign button visibility data in batch to prevent N+1 queries
     */
    public function getBatchCampaignVisibility(array $campaignIds): array
    {
        if (empty($campaignIds)) {
            return [];
        }

        // Single query to get all visibility data
        $visibilityData = EmailCampaignRecipient::whereIn('email_campaign_id', $campaignIds)
            ->selectRaw('
                email_campaign_id,
                COUNT(*) as total,
                SUM(CASE WHEN status = "sent" THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending
            ')
            ->groupBy('email_campaign_id')
            ->get()
            ->keyBy('email_campaign_id');

        $result = [];
        foreach ($campaignIds as $campaignId) {
            $stats = $visibilityData->get($campaignId);

            if ($stats) {
                $result[$campaignId] = [
                    'is_completely_successful' => $stats->sent == $stats->total && $stats->failed == 0 && $stats->pending == 0,
                    'has_failed_emails' => $stats->failed > 0,
                    'failed_count' => $stats->failed,
                ];
            } else {
                $result[$campaignId] = [
                    'is_completely_successful' => false,
                    'has_failed_emails' => false,
                    'failed_count' => 0,
                ];
            }
        }

        return $result;
    }

    /**
     * Force update campaign progress (bypasses throttling) - Legacy method
     */
    public function forceUpdateCampaignProgress(int $campaignId): void
    {
        $this->syncCampaignStats($campaignId);
    }
}
