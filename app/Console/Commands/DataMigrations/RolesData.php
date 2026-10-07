<?php

namespace App\Console\Commands\DataMigrations;

use App\Models\Role;
use Illuminate\Support\Facades\DB;

class RolesData
{
    public static function execute()
    {
        $mmb1Data = DB::connection('mmb1')
            ->table('roles')
            ->get();

        $mmb2Data = [];

        foreach ($mmb1Data as $key => $value) {
            $mmb2Data[] = [
                'id' => $value->id,
                'name' => $value->name,
                'guard_name' => $value->guard_name,
            ];
        }

        foreach (array_chunk($mmb2Data, 1000) as $chunk) {
            Role::insert($chunk);
        }

        return true;
    }
}
