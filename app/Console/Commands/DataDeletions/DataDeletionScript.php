<?php

namespace App\Console\Commands\DataDeletions;

use App\Models\CheckpointLog;
use App\Models\Invoice;
use App\Models\Maintenance;
use App\Models\ResidenceAmenity;
use App\Models\ResidenceAmenityOption;
use App\Models\Unit;
use App\Models\VisitorLog;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;

class DataDeletionScript extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data-deletion:modules {--R|residence=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run data deletion SOP for modules.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $residenceInput = $this->option('residence');

        if (empty($residenceInput) == false) {

            // Visitors
            $this->vms($residenceInput);

            // // CheckpointLog
            $this->pgs($residenceInput);

            // Bill Reminder
            $this->billReminder($residenceInput);

            // Maintenance
            $this->maintenance($residenceInput);
        } else {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function vms(int $residenceInput): void
    {
        $query = VisitorLog::whereHas('visitingArrangements', function ($q) use ($residenceInput) {
            $q->where('residence_id', $residenceInput);
        });
        $totalData = $query->count();

        $bar = $this->output->createProgressBar($totalData);

        if ($this->confirm("VMS Data to be deleted: $totalData. Do you wish to continue?")) {
            $bar->start();

            $query->chunk(200, function ($visitorLogs) use ($bar) {
                foreach ($visitorLogs as $visitorLog) {
                    $this->performTask($visitorLog);
                    $bar->advance();
                }
            });

            $bar->finish();
        }
    }

    private function pgs(int $residenceInput): void
    {
        $query = CheckpointLog::whereHas('checkpoint', function ($q) use ($residenceInput) {
            $q->where('mmb_residence_id', $residenceInput);
        });
        $totalData = $query->count();
        $bar = $this->output->createProgressBar($totalData);

        if ($this->confirm("PGS Data to be deleted: $totalData. Do you wish to continue?")) {
            $bar->start();

            $query->chunk(200, function ($checkpointLogs) use ($bar) {
                foreach ($checkpointLogs as $checkpointLog) {
                    $this->performTask($checkpointLog);
                    $bar->advance();
                }
            });

            $bar->finish();
        }
    }

    private function billReminder(int $residenceInput): void
    {
        $query = Invoice::whereHas('BillPayeeSetting', function ($q) use ($residenceInput) {
            $q->where('residence_id', $residenceInput);
        });

        $totalData = $query->count();

        $bar = $this->output->createProgressBar($totalData);

        if ($this->confirm("Bill Reminder Data to be deleted: $totalData. Do you wish to continue?")) {
            $bar->start();

            $query->chunk(200, function ($billReminders) use ($bar) {
                foreach ($billReminders as $billReminder) {
                    $this->performTask($billReminder);
                    $bar->advance();
                }
            });

            $bar->finish();
        }
    }

    private function maintenance(int $residenceInput)
    {
        $query = Maintenance::whereHasMorph(
            'maintainable',
            [Unit::class, ResidenceAmenity::class, ResidenceAmenityOption::class],
            function ($q) use ($residenceInput) {
                $q->where('residence_id', $residenceInput);
            });

        $totalData = $query->count();

        $bar = $this->output->createProgressBar($totalData);

        if ($this->confirm("Maintenance Data to be deleted: $totalData. Do you wish to continue?")) {
            $bar->start();

            $query->chunk(200, function ($maintenances) use ($bar) {
                foreach ($maintenances as $maintenance) {
                    $this->performTask($maintenance);
                    $bar->advance();
                }
            });
        }

        $bar->finish();
    }

    public function performTask(?Model $model): void
    {
        $className = class_basename($model);

        if ($className == 'VisitorLog') {
            $model->visitingArrangements()->delete();
        }

        if ($className == 'Invoice') {
            if (empty($model->transactions() == false)) {
                $model->transactions()->delete();

                if ($model->transactions()->delete()) {
                    $model->transactions()->clearMediaCollection();
                }
            }

            if (empty($model->payers() == false)) {
                $model->payers()->delete();
            }

            $model->items()->delete();
        }

        if ($className == 'Maintenance') {
            $model->maintenanceProgressions()->delete();

            if ($model->maintenanceProgressions()->delete()) {
                $model->maintenanceProgressions()->clearMediaCollection();
            }

            $model->comments()->delete();

            if ($model->comments()->delete()) {
                $model->comments()->clearMediaCollection();
            }
        }

        $model->delete();

        if ($model->delete()) {
            $model->clearMediaCollection();
        }
    }
}
