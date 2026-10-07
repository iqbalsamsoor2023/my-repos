<?php

namespace App\Console\Commands\DataMigrations;

use App\Models\AppVersion;
use Exception;
use Illuminate\Support\Facades\DB;

class ApplicationVersionsData
{
    public static function execute($residence_id)
    {
        try {
            DB::beginTransaction();
            $mmb1Data = DB::connection('mmb1')
                ->table('app_versions')
                ->get();

            foreach ($mmb1Data as $key => $value) {
                AppVersion::insert([
                    'id' => $value->id,
                    'platform' => $value->platform,
                    'application_name' => $value->application_name,
                    'package_identifier' => $value->package_identifier,
                    'build_version' => $value->build_version,
                    'application_version' => $value->application_version,
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
