<?php

namespace App\Jobs\Visitor;

use App\Models\VisitingArrangement;
use App\Notifications\VisitorArrived;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SendVisitorArrivedNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $visitorLogId,
        public array $userIds
    ) {
        $this->onQueue('VisitorQueue');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Fetch all arrangements with their users in one query
        $arrangements = VisitingArrangement::with('user')
            ->where('visitor_log_id', $this->visitorLogId)
            ->whereIn('user_id', $this->userIds)
            ->whereNotNull('user_id')
            ->get();

        // Group by user to avoid sending duplicate notifications
        $notificationsByUser = $arrangements->groupBy('user_id');

        foreach ($notificationsByUser as $userId => $userArrangements) {
            $user = $userArrangements->first()->user;

            if ($user) {
                try {
                    // If multiple arrangements for same user, send notification for the first one
                    // or customize logic to send all arrangements
                    Notification::send($user, new VisitorArrived($userArrangements->first()));
                } catch (\Exception $e) {
                    Log::error('Failed to send visitor notification', [
                        'user_id' => $userId,
                        'arrangement_ids' => $userArrangements->pluck('id')->toArray(),
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendVisitorArrivedNotifications job failed', [
            'visitor_log_id' => $this->visitorLogId,
            'user_ids' => $this->userIds,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
