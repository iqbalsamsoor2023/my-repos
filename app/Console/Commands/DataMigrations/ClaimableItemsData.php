<?php

namespace App\Console\Commands\DataMigrations;

use Exception;
use Illuminate\Support\Facades\DB;

class ClaimableItemsData
{
    public function execute($residence_id)
    {
        try {
            DB::beginTransaction();
            $mmb1Data = DB::connection('mmb1')
                ->table('facilities')
                ->where('residence_id', $residence_id)
                ->orderBy('facilities.id', 'asc')
                ->chunk(1000, function ($datas) {
                    foreach ($datas as $key => $value) {
                        if ($value->status == 0) {
                            $deleted_at = now();
                        }

                        DB::table('claimable_items')->insert([
                            'residence_id' => $value->residence_id,
                            'item' => $value->name,
                            'created_at' => $value->created_at,
                            'updated_at' => $value->updated_at,
                            'deleted_at' => $deleted_at ?? null,
                        ]);
                    }
                });

            DB::table('claimable_items')->insert([
                'residence_id' => $residence_id,
                'item' => 'Others',
                'created_at' => now(),
            ]);

            DB::commit();

            return true;
        } catch (Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }
}
