<?php

namespace App\Console\Commands;

use App\Enums\AutoSendReport\ModuleType;
use App\Jobs\IncidentASRJob;
use App\Jobs\PatrolASRJob;
use App\Jobs\VisitorASRJob;
use App\Models\AutoSendReport as ModelsAutoSendReport;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

class AutoSendReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:autoSendEmail
    {--datetime= : Simulate trigger datetime ("example: 12/30 23:59")}
    {--mooban= : Only trigger for a particulat residence ("example: 02219)}
    {--email= : Only send to stated email ("example: mymooban@mymooban.co.th)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Running every minutes to send email for export Data for IRS, PGS and Visitor.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $simulatedDateTime = $this->option('datetime');
        $residence_id = (int) $this->option('mooban');
        $email = $this->option('email');

        if (empty($simulatedDateTime) == false) {
            $timeNow = Carbon::create("$simulatedDateTime");
        } else {
            $timeNow = Carbon::now();
        }

        $autoSendReports = ModelsAutoSendReport::where('time', $timeNow->format('H:i'));

        if (empty($residence_id) == false) {
            $autoSendReports = $autoSendReports->where('residence_id', $residence_id);
        }

        if (empty($email) == false) {
            $autoSendReports = $autoSendReports->where('email', $email);
        }

        $autoSendReports = $autoSendReports->get();

        if ($autoSendReports->count() == 0) {
            return true;
        }

        $this->showTable($autoSendReports);

        foreach ($autoSendReports as $autoSendReport) {
            if (empty($autoSendReport->module_type)) {
                throw new Exception('Module type is empty');
            }

            switch ($autoSendReport->module_type) {
                case ModuleType::VISITOR:
                    VisitorASRJob::dispatch($autoSendReport, $timeNow);
                    break;

                case ModuleType::PGS:
                    PatrolASRJob::dispatch($autoSendReport, $timeNow);
                    break;

                case ModuleType::IRS:
                    IncidentASRJob::dispatch($autoSendReport, $timeNow);
                    break;
            }
        }

        return Command::SUCCESS;
    }

    private function showTable(Collection $autoSendReports)
    {
        $autoSendReport = $autoSendReports->first();
        $header = array_keys($autoSendReport->getAttributes());
        $this->table($header, $autoSendReports->toArray());
    }
}
