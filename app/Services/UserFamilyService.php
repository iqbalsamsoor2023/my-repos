<?php

namespace App\Services;

use App\Actions\UnitUser\CreateUnitUserAction;
use App\Actions\UnitUser\DeleteUnitUserAction;
use App\Actions\UnitUser\UpdateUnitUserAction;
use App\Actions\User\CreateUserAction;
use App\Actions\User\GetUserAction;
use App\Http\Requests\UserFamily\StoreUserFamilyRequest;
use App\Http\Requests\UserFamily\UpdateUserFamilyRequest;
use App\Mail\UserRegistered as MailUserRegistered;
use App\Models\UnitUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class UserFamilyService
{
    public function index(int $unitUserId)
    {
        return DB::transaction(function () use ($unitUserId) {
            $unitUser = UnitUser::findOrFail($unitUserId);
            $unitUsers = UnitUser::where('unit_id', $unitUser->unit_id)
                ->where('id', '!=', $unitUserId)
                ->where('is_owner', $unitUser->is_owner)
                ->with('user')
                ->get();

            return $unitUsers;
        });
    }

    public function create(StoreUserFamilyRequest $request)
    {
        return DB::transaction(function () use ($request) {
            // send mail to new user if created via family
            if ($request->has('created_via_family') && $request->created_via_family == false) {
                $userAction = new GetUserAction;
                $user = $userAction->execute($request);
            } else {
                $randomPassword = Str::random(8);
                $request->merge(['password' => $randomPassword]);
                $userAction = new CreateUserAction;
                $user = $userAction->execute($request);
                Mail::to($user->email)->send(new MailUserRegistered($user, $user->email, $randomPassword));
            }

            $unitUserAction = new CreateUnitUserAction;
            $unitUser = $unitUserAction->execute($request, $user->id);
            $unitUser->user = $user;

            return $unitUser;
        });
    }

    public function update(UpdateUserFamilyRequest $request, int $id)
    {
        $unit_user = UnitUser::findOrFail($id);

        $unitUserAction = new UpdateUnitUserAction;
        $user = $unitUserAction->execute($request, $unit_user);

        return $user;
    }

    public function delete(int $id)
    {
        $unit_user = UnitUser::findOrFail($id);

        $unitUserAction = new DeleteUnitUserAction;
        $unit_user = $unitUserAction->execute($unit_user);

        return $unit_user;
    }
}
