<?php

namespace App\Repositories;

use App\Actions\UnitUser\CreateUnitUserAction;
use App\Actions\UnitUser\DeleteUnitUserAction;
use App\Actions\UnitUser\UpdateUnitUserAction;
use App\Actions\User\CreateUserAction;
use App\Http\Requests\UnitTenant\StoreUnitTenantRequest;
use App\Http\Requests\UnitTenant\UpdateUnitTenantRequest;
use App\Interfaces\UnitTenantRepositoryInterface;
use App\Mail\UserRegistered as MailUserRegistered;
use App\Models\UnitUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class UnitTenantRepository implements UnitTenantRepositoryInterface
{
    public function create(StoreUnitTenantRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $userAction = new CreateUserAction;
            $unitUserAction = new CreateUnitUserAction;

            $user = $userAction->execute($request);
            $unitUserAction->execute($request, $user->id);
            Mail::to($user->email)->send(new MailUserRegistered($user));

            return $user;
        });
    }

    public function update(UpdateUnitTenantRequest $request, $id)
    {
        $unitUser = UnitUser::findOrFail($id);
        $unitUserAction = new UpdateUnitUserAction;
        $unitUser = $unitUserAction->execute($request, $unitUser);

        return $unitUser;
    }

    public function delete(int $id)
    {
        $unitUser = UnitUser::findOrFail($id);
        $unitUserAction = new DeleteUnitUserAction;

        return $unitUserAction->execute($unitUser);

        return $unitUser;
    }
}
