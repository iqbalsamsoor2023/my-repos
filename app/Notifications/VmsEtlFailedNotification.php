<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;

class VmsEtlFailedNotification extends Notification
{
    use Queueable;

    protected \Throwable $exception;
    protected bool $isFinalAttempt;
    protected int $attemptNumber;

    /**
     * Create a new notification instance.
     */
    public function __construct(\Throwable $exception, bool $isFinalAttempt = false, int $attemptNumber = 1)
    {
        $this->exception = $exception;
        $this->isFinalAttempt = $isFinalAttempt;
        $this->attemptNumber = $attemptNumber;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['slack'];
    }

    /**
     * Get the Slack representation of the notification.
     */
    public function toSlack(object $notifiable): SlackMessage
    {
        $emoji = $this->isFinalAttempt ? ':rotating_light:' : ':warning:';
        $status = $this->isFinalAttempt ? 'CRITICAL - All Retries Exhausted' : 'FAILED';
        $color = $this->isFinalAttempt ? 'danger' : 'warning';

        $message = (new SlackMessage)
            ->error()
            ->from('VMS ETL Monitor', $emoji)
            ->to(config('services.slack.vms_etl_channel', '#vms-alerts'))
            ->content("{$emoji} *VMS ETL Job {$status}*")
            ->attachment(function ($attachment) use ($color) {
                $attachment
                    ->color($color)
                    ->title('Error Details')
                    ->fields([
                        'Environment' => config('app.env'),
                        'Application' => config('app.name'),
                        'Timestamp' => now()->format('Y-m-d H:i:s T'),
                        'Attempt' => "{$this->attemptNumber}/3",
                        'Error Message' => $this->truncate($this->exception->getMessage(), 300),
                        'Error Type' => get_class($this->exception),
                        'File' => basename($this->exception->getFile()) . ':' . $this->exception->getLine(),
                    ]);
            });

        if ($this->isFinalAttempt) {
            $message->attachment(function ($attachment) {
                $attachment
                    ->color('danger')
                    ->title('⚠️ Action Required')
                    ->content("The VMS ETL sync job has failed permanently. Please investigate immediately:\n" .
                        "• Check application logs: `tail -f storage/logs/vms_etl.log`\n" .
                        "• Review database connectivity\n" .
                        "• Verify queue workers are running\n" .
                        "• Run manual sync: `php artisan vms:sync-report`");
            });
        }

        return $message;
    }

    /**
     * Truncate text to specified length.
     */
    protected function truncate(string $text, int $length = 200): string
    {
        if (strlen($text) <= $length) {
            return $text;
        }

        return substr($text, 0, $length) . '...';
    }
}
