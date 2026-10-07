<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrphanVisitorCleanupReport extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public int $deletedTotal,
        public float $executionTime,
        public int $rate,
        public mixed $lastVisitorId,
        public string $csvPath,
        public bool $completedSuccessfully,
    ) {
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $status = $this->completedSuccessfully
            ? 'COMPLETED'
            : 'FAILED / PARTIAL';

        return $this
            ->subject(
                "Visitor Orphan Cleanup Report - {$status}"
            )
            ->view(
                'emails.visitors.orphan-cleanup-report'
            )
            ->with([
                'deletedTotal' =>
                    $this->deletedTotal,

                'executionTime' =>
                    $this->executionTime,

                'rate' =>
                    $this->rate,

                'lastVisitorId' =>
                    $this->lastVisitorId,

                'completedSuccessfully' =>
                    $this->completedSuccessfully,
            ])
            ->attach(
                $this->csvPath,
                [
                    'as' =>
                        'deleted-visitor-ids.csv',

                    'mime' =>
                        'text/csv',
                ]
            );
    }
}
