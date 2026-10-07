<?php

namespace App\Console\Commands\DataMigrations;

use App\Models\VisitorPurpose;
use Exception;
use Illuminate\Support\Facades\DB;

class VisitorPurposesData
{
    public static function execute($residence_id)
    {
        try {
            DB::beginTransaction();
            $mmb1Data = DB::connection('mmb1')
                ->table('purpose_of_visits')
                ->where('residence_id', $residence_id)
                ->orderBy('purpose_of_visits.id')
                ->chunk(1000, function ($datas) {
                    foreach ($datas as $key => $value) {
                        $visitor_purposes = VisitorPurpose::create([
                            'residence_id' => $value->residence_id,
                            'purpose' => strlen($value->name) != strlen(utf8_decode($value->name)) ? $value->name : $value->name,
                            'created_at' => $value->created_at,
                            'updated_at' => $value->updated_at,
                            'deleted_at' => $value->deleted_at,
                        ]);
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
