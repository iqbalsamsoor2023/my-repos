<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeactiveMMB1Users extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'delete:deactiveMMB1User
    {--residence_id= : Only trigger for a particulat residence ("example: 02219)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'To deactivate PM and SC mmb1 users after migration done';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $residence_id = $this->option('residence_id');

        if (! isset($residence_id)) {
            $this->info('Operation aborted');
        }

        DB::connection('mmb1')->statement('SET FOREIGN_KEY_CHECKS = 0;');

        // delete property management
        $property_management = DB::connection('mmb1')
            ->table('users')
            ->leftJoin('user_profiles', 'user_profiles.user_id', 'users.id')
            ->join('residence_managements', 'residence_managements.user_id', '=', 'users.id')
            ->where('residence_managements.residence_id', $residence_id)
            ->update(['users.deleted_at' => Carbon::now()]);

        // delete sc user
        $mmb1_residence_guard = DB::connection('mmb1')
            ->table('residence_guards')
            ->select('*', 'residence_guards.id as id')
            ->leftJoin('model_has_roles', 'model_has_roles.model_id', 'residence_guards.user_id')
            ->where('model_has_roles.role_id', 8) // security guard
            ->where('residence_guards.residence_id', $residence_id)
            ->first();

        $mmb1_sc_user = DB::connection('mmb1')
            ->table('users')->where('id', $mmb1_residence_guard->user_id)
            ->update(['deleted_at' => Carbon::now()]);

        DB::connection('mmb1')->statement('SET FOREIGN_KEY_CHECKS = 1;');
    }
}
