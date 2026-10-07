<?php

namespace App\Jobs;

use App\Exports\VisitorsASRExport;
use App\Mail\AutoSendReport\VisitorASRMail;
use App\Models\AutoSendReport;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class VisitorASRJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $autoSendReport;

    protected $timeNow;

    // 1 hours
    public $timeout = 3600;

    public $tries = 1;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(AutoSendReport $autoSendReport, ?Carbon $timeNow = null)
    {
        $this->onQueue('asrVisitorQueue');

        $this->autoSendReport = $autoSendReport;
        $this->timeNow = $timeNow;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $residence = $this->autoSendReport->residence;
        $residence_id = $residence->id;
        $date = date('Ymd');
        $fileName = "visitor_report/VisitorReport-$date-$residence_id.xlsx";

        Excel::store(new VisitorsASRExport($this->autoSendReport, $this->timeNow), $fileName);
        Mail::to($this->autoSendReport->email)->send(new VisitorASRMail(
            $residence,
            $this->autoSendReport,
            $this->timeNow,
            storage_path("app/$fileName")
        ));

        Storage::delete($fileName);

        return true;
    }
}
