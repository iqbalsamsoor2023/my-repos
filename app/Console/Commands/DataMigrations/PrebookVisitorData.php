<?php

namespace App\Console\Commands\DataMigrations;

use App\Jobs\MigratePrebookVisitorJob;
use Exception;
use Illuminate\Support\Facades\DB;

class PrebookVisitorData
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prebook visitor only within 1 year';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function execute($residence_id)
    {
        try {
            DB::beginTransaction();
            $mmb1Data = DB::connection('mmb1')
                ->table('visitors')
                ->where('visitors.residence_id', $residence_id)
                ->orderBy('visitors.id')
                ->chunk(300, function ($datas, $unit) use ($residence_id) {
                    foreach ($datas as $key => $value) {
                        MigratePrebookVisitorJob::dispatch($value, $residence_id);
                    }
                });

            DB::commit();

            return true;
        } catch (Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }
}
