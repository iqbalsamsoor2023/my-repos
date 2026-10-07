<?php

namespace App\Console\Commands\DataMigrations;

use App\Models\AutoSendReport;
use App\Models\Residence;
use Exception;
use Illuminate\Support\Facades\DB;

class AutoSendReportsData
{
    public static function execute($residence_id)
    {
        try {
            DB::beginTransaction();
            $mmb1_data = DB::connection('mmb1')
                ->table('auto_send_reports')
                ->select('*', 'auto_send_reports.id as id', 'auto_send_reports.created_at as created_at', 'auto_send_reports.updated_at as updated_at', 'residences.name as residence_name')
                ->leftJoin('residences', 'residences.id', 'auto_send_reports.residence_id')
                ->where('auto_send_reports.residence_id', $residence_id)
                ->get();

            foreach ($mmb1_data as $key => $value) {
                $residence = Residence::withTrashed()->where('name', $value->residence_name)->latest()->first();

                AutoSendReport::create([
                    'email' => $value->email,
                    'residence_id' => $residence->id,
                    'time' => $value->time,
                    'hour' => $value->hour,
                    'module_type' => $value->module_type,
                    'created_at' => $value->created_at,
                    'updated_at' => $value->updated_at,
                ]);
            }
            DB::commit();

            return true;
        } catch (Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }
}
