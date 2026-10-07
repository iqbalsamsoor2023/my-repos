<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeleteMMB1Units extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'delete:deleteMMB1Unit
    {--residence_id= : Only trigger for a particulat residence ("example: 02219)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete MMB1 units after data migration complete';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle($residence_id)
    {
        $mmb1Data = DB::connection('mmb1')
            ->table('residence_units')
            ->where('residence_id', $residence_id)
            ->orderBy('id', 'asc')
            ->chunk(1000, function ($datas) {
                foreach ($datas as $key => $value) {
                    $residence_users = DB::connection('mmb1')
                        ->table('residence_users')
                        ->where('residence_unit_id', $value->id)
                        ->delete();
                }
            });
    }
}
