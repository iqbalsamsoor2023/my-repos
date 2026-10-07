<?php

namespace App\Console\Commands\DataMigrations;

use App\Models\BlacklistedVisitor;
use App\Models\Visitor;
use Exception;
use Illuminate\Support\Facades\DB;

class BlacklistedVisitorsData
{
    public static function execute($residence_id)
    {
        try {
            $mmb1Data = DB::connection('mmb1')
                ->table('visitor_blacklists')
                ->where('residence_id', $residence_id)
                ->orderBy('visitor_blacklists.id')
                ->chunk(1000, function ($datas) {
                    $mmb2Data = [];

                    foreach ($datas as $key => $value) {
                        $data = [
                            'name' => $value->name,
                            'contact_no' => null,
                            'id_type' => $value->nationality == 1 ? 1 : 2,
                            'id_number' => $value->nationality == 1 ? $value->thai_id : $value->passport_number,
                            'created_at' => $value->created_at,
                            'updated_at' => $value->updated_at,
                        ];
                        $visitor = Visitor::firstOrCreate($data, $data);
                        $blacklist_visitor = BlacklistedVisitor::create([
                            'visitor_id' => $visitor->id,
                            'residence_id' => $value->residence_id,
                            'blacklist_remark' => $value->remark,
                            'vehicle_plate_no' => $value->plate_number,
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
