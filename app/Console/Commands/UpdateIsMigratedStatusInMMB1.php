<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateIsMigratedStatusInMMB1 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:isMigratedStatus
    {--residence_id= : Only trigger for a particulat residence ("example: 02219)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'To update is migrated status in residence table mmb1 to activate the update notice pop up';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $residence_id = $this->option('residence_id');

        DB::connection('mmb1')
            ->table('residences')
            ->where('id', $residence_id)
            ->update([
                'is_migrate' => 1,
            ]);
    }
}
