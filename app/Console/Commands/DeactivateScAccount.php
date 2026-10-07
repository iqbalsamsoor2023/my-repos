<?php

namespace App\Console\Commands;

use App\Enums\Residence\Features;
use App\Models\ResidenceFeature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Log;

class DeactivateScAccount extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:deactivate-sc-account';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deactivate all SC accounts for unmigrated mooban';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $unmigrated_residences = DB::connection('mmb1')
            ->table('residences')
            ->where('is_migrate', 0)
            ->get();

        $total = count($unmigrated_residences);

        if ($this->confirm("Data to be updated: $total. Do you wish to continue?")) {
            $bar = $this->output->createProgressBar($total);
            $bar->start();
            foreach ($unmigrated_residences as $residence) {
                $updated_residence = ResidenceFeature::where('residence_id', $residence->id)
                    ->where('feature_id', Features::SECURITY_MANAGEMENT->value)
                    ->update([
                        'is_active' => 0,
                    ]);

                if (! $updated_residence) {
                    Log::info('Failed! '.$residence->id);
                }
                $bar->advance();
            }
        }

        $bar->finish();

        return Command::SUCCESS;
    }
}
