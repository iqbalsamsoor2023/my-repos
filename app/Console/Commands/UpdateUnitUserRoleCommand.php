<?php

namespace App\Console\Commands;

use App\Enums\User\RoleType;
use App\Models\UnitUser;
use Illuminate\Console\Command;

class UpdateUnitUserRoleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-unit-user-role-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Existing Data which unit user role is not correct';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $unitUsers = UnitUser::select('id')->with('user')->get();

        $bar = $this->output->createProgressBar(count($unitUsers));
        $bar->start();

        UnitUser::chunk(100, function ($chunkedUnitUsers) use ($bar) {
            foreach ($chunkedUnitUsers as $unitUser) {
                $user = $unitUser->user;

                if ($user) {
                    $hasUnitOwnerRole = $user->roles->contains(function ($role) {
                        return $role->name == RoleType::UNIT_OWNER->value;
                    });

                    $hasUnitTenantRole = $user->roles->contains(function ($role) {
                        return $role->name == RoleType::UNIT_TENANT->value;
                    });

                    // Skip if the user has both roles
                    if ($hasUnitOwnerRole && $hasUnitTenantRole) {
                        continue; // Skip this user
                    }

                    if ($hasUnitOwnerRole) {
                        $unitUser->is_owner = 1;
                    } else {
                        $unitUser->is_owner = 0;
                    }

                    $unitUser->save();
                }

                $bar->advance();
            }
        });

        $bar->finish();
    }
}
