<?php

namespace App\Console\Commands\DataMigrations;

use App\Models\VisitorRemark;
use Exception;
use Illuminate\Support\Facades\DB;

class VisitorRemarksData
{
    public static function execute($residence_id)
    {
        try {
            DB::beginTransaction();
            $mmb1Data = DB::connection('mmb1')
                ->table('visitor_remarks')
                ->where('residence_id', $residence_id)
                ->orderBy('id', 'asc')
                ->chunk(1000, function ($datas) {
                    foreach ($datas as $key => $value) {
                        $visitor_remark = VisitorRemark::create([
                            'residence_id' => $value->residence_id,
                            'remark' => $value->remark,
                            'created_at' => $value->created_at,
                            'updated_at' => $value->updated_at,
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
