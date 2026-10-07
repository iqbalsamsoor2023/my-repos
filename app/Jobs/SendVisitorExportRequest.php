<?php

namespace App\Jobs;

use App\Services\VisitorLogService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendVisitorExportRequest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $exportRequest;

    public function __construct(array $exportRequest)
    {
        $this->exportRequest = $exportRequest;
    }

    public function handle(): void
    {
        try {
            $visitorLogService = app(VisitorLogService::class);
            $result = $visitorLogService->export($this->exportRequest);

            Log::info('Visitor export completed', [
                'user_id' => $this->exportRequest['user_id'] ?? null,
                'project' => $this->exportRequest['project'] ?? null,
                'format' => $this->exportRequest['format'] ?? null
            ]);

        } catch (\Exception $e) {
            Log::error('Visitor export failed', [
                'user_id' => $this->exportRequest['user_id'] ?? null,
                'project' => $this->exportRequest['project'] ?? null,
                'error' => $e->getMessage()
            ]);

            // Re-throw the exception to mark the job as failed
            throw $e;
        }
    }
}
