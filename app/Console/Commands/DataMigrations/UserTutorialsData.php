<?php

namespace App\Console\Commands\DataMigrations;

use App\Models\UserTutorial;
use Exception;
use Illuminate\Support\Facades\DB;

class UserTutorialsData
{
    public static function execute($residence_id)
    {
        try {
            DB::beginTransaction();
            DB::connection('mmb1')
                ->table('user_tutorials')
                ->orderBy('id', 'asc')
                ->chunk(1000, function ($datas) {
                    foreach ($datas as $key => $value) {
                        $user_tuto = UserTutorial::create([
                            'module' => $value->model,
                            'title' => $value->name,
                            'type' => 1,
                            'file_name' => $value->embed_link,
                            'created_at' => $value->created_at,
                            'updated_at' => $value->updated_at,
                        ]);
                    }
                });

            DB::connection('mmb1')
                ->table('user_app_tutorials')
                ->orderBy('id', 'asc')
                ->chunk(1000, function ($datas) {
                    foreach ($datas as $key => $value) {
                        $user_tuto = UserTutorial::create([
                            'module' => null,
                            'title' => $value->name,
                            'type' => 2,
                            'file_name' => $value->slug,
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
