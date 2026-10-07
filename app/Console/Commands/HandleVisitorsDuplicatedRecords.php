<?php

namespace App\Console\Commands;

use Throwable;
use App\Helpers\VisitorHelper;
use App\Models\Visitor;
use App\Models\VisitorLog;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HandleVisitorsDuplicatedRecords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vms:update-visitor-number {--test}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update duplicated visitors number for case 3.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $isTest = $this->option('test');
        $isSameVisitorNo = false;
        $isSameData = false;
        $isSameArrivalTime = false;

        $duplicatedRecords = VisitorLog::select(DB::raw('visitor_generated_no, count(*) as duplicates'))
            ->withTrashed()
            ->groupBy('visitor_generated_no')
            ->having('duplicates', '>', 1)
            ->get();

        // dd($duplicatedRecords->toSql());
        // dd($duplicatedRecords->sum('duplicates'));

        $bar = $this->output->createProgressBar($duplicatedRecords->sum('duplicates'));
        $bar->start();

        foreach ($duplicatedRecords as $key => $duplicatedRecord) {
            $visitorLogs = VisitorLog::where('visitor_generated_no', $duplicatedRecord->visitor_generated_no)->withTrashed()->get();

            $visitorLog1 = $visitorLogs->get(0);

            for ($i = 1; $i < $visitorLogs->count(); $i++) {
                $visitorLog2 = $visitorLogs->get($i);

                $isSameVisitorNo = $this->isSameVisitorNumber($visitorLog1, $visitorLog2);
                $isSameData = $this->isSameData($visitorLog1, $visitorLog2);
                $isSameArrivalTime = $this->isSameArrivalTime($visitorLog1, $visitorLog2);

                // Scenario 1
                if ($isSameVisitorNo == true && $isSameData == false) {
                    // Regenerate

                    if ($isTest) {
                        Log::info("Case 1: $visitorLog1->id");
                    } else {
                        $this->regenerateVisitorGeneratedNumber($visitorLog2);
                    }
                } elseif ($isSameVisitorNo == true && $isSameData == true && $isSameArrivalTime == true) {
                    // Delete

                    if ($isTest) {
                        Log::info("Case 2: $visitorLog1->id");
                    } else {
                        $visitorLog2->visitingArrangements()->forceDelete();
                        $visitorLog2->visitorParking()->forceDelete();
                        $visitorLog2->forceDelete();
                    }
                } elseif ($isSameVisitorNo == false && $isSameData == true && $isSameArrivalTime == true) {
                    // Delete

                    if ($isTest) {
                        Log::info("Case 3: $visitorLog1->id");
                    } else {
                        $visitorLog2->visitingArrangements()->forceDelete();
                        $visitorLog2->visitorParking()->forceDelete();
                        $visitorLog2->forceDelete();
                    }
                } else {
                    // Regenerate

                    if ($isTest) {
                        Log::info("Case 4: $visitorLog2->id");
                    } else {
                        $this->regenerateVisitorGeneratedNumber($visitorLog2);
                    }
                }
                $bar->advance();
            }
        }

        $bar->finish();

        return Command::SUCCESS;
    }

    private function regenerateVisitorGeneratedNumber(VisitorLog $visitorLog2): VisitorLog
    {
        $attempts = 0;

        do {
            try {
                $visitorLog2->visitor_generated_no = VisitorHelper::generateVisitorNo($visitorLog2->visitingArrangements->first()->residence_id);
                $visitorLog2->save();
                break;
            } catch (Throwable $th) {
                $attempts++;
                throw $th;
            }
        } while ($attempts <= 5);

        return $visitorLog2;
    }

    private function isSameVisitorNumber(VisitorLog $visitorLog1, VisitorLog $visitorLog2): bool
    {
        return $visitorLog1->visitor_generated_no == $visitorLog2->visitor_generated_no;
    }

    private function isSameData(VisitorLog $visitorLog1, VisitorLog $visitorLog2): bool
    {
        // House unit
        if ($visitorLog1->visitingArrangements->pluck('unit_id') != $visitorLog2->visitingArrangements->pluck('unit_id')) {
            return false;
        }

        // Visitor name
        if ($visitorLog1->visitor->name != $visitorLog2->visitor->name) {
            return false;
        }

        // Visitor card
        if ($visitorLog1->visitor_card_id != $visitorLog2->visitor_card_id) {
            return false;
        }

        // Visitor purpose
        if ($visitorLog1->visitor_purpose != $visitorLog2->visitor_purpose) {
            return false;
        }

        // Arrival type
        if ($visitorLog1->arrival_type != $visitorLog2->arrival_type) {
            return false;
        }

        // Vehicle type
        if ($visitorLog1->vehicle_type != $visitorLog2->vehicle_type) {
            return false;
        }

        // Vehicle plate number
        if ($visitorLog1->vehicle_plate_no != $visitorLog2->vehicle_plate_no) {
            return false;
        }

        return true;
    }

    private function isSameArrivalTime(VisitorLog $visitorLog1, VisitorLog $visitorLog2): bool
    {
        return (new Carbon($visitorLog1->arrival_time))->toDayDateTimeString() == (new Carbon($visitorLog2->arrival_time))->toDayDateTimeString();
    }
}
