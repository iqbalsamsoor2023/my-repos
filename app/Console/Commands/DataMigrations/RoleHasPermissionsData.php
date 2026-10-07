<?php

namespace App\Console\Commands\DataMigrations;

use Illuminate\Support\Facades\DB;

class RoleHasPermissionsData
{
    // deprecated. we use policy.
    public static function execute()
    {
        $mmb1Data = DB::connection('mmb1')
            ->table('role_has_permissions')
            ->get();

        $mmb2Data = [];

        foreach ($mmb1Data as $key => $value) {
            $mmb2Data[] = (array) $value;
        }

        foreach (array_chunk($mmb2Data, 1000) as $chunk) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::table('role_has_permissions')->insert($chunk);
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        return true;
    }
}
