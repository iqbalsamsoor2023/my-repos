<?php

namespace App\Services;

use App\Actions\Notification\ReadAllNotificationAction;
use App\Actions\UnitUser\CreateUnitUserAction;
use App\Actions\User\ChangePasswordAction;
use App\Actions\User\CreateUserAction;
use App\Actions\User\DeleteUserAction;
use App\Actions\User\UpdateProfilePictureAction;
use App\Actions\User\UpdateUserAction;
use App\Http\Requests\User\ChangePasswordRequest;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateProfilePictureRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Requests\UserSearchRequest;
use App\Mail\UserRegistered as MailUserRegistered;
use App\Models\Residence;
use App\Models\User;
use App\Notifications\UserRegistered;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

class UserService
{
    public function search(UserSearchRequest $request)
    {
        $user = User::query();

        $user->when($request->has('email'), fn ($query) => $query->where('email', $request->email));

        return $user->with(['unitUsers' => function ($query) use ($request) {

            // select only the columns needed for my family update purpose
            $query->select('id', 'user_id', 'unit_id');

            $query->when($request->has('unit_id'), function ($query) use ($request) {
                $query->where('unit_id', $request->unit_id);
            });
        }])->firstOrFail();
    }

    public function create(StoreUserRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $createUserAction = new CreateUserAction;
            $createUnitUserAction = new CreateUnitUserAction;
            $plainPassword = $request->password;

            $user = $createUserAction->execute($request);
            $createUnitUserAction->execute($request, $user->id);
            $residence = Residence::with('propertyManagementUser')->where('id', $request->residence_id)->first();
            $pmUser = User::where('id', $residence->property_management_user_id)->first();
            $pmUser->notify(new UserRegistered($user));

            $platform = $request->header('X-App-Platform') ?? 'N/A';
            $appVersionCode = $request->header('X-App-Version') ?? 0;
            $appName = $request->header('X-App-Name') ?? 'N/A';
            if ($appName == 'user-app') {
                if (($platform == 'android' && $appVersionCode >= env('ANDROID_VERSION_CODE', 276)) || ($platform == 'ios' && $appVersionCode >= env('IOS_VERSION_CODE', 300))) {
                    Cache::set('user-app-new-user-'.$user->id, true);
                }
            }

            Mail::to($user->email)->send(new MailUserRegistered($user, $user->email, $plainPassword));

            return $user;
        });
    }

    public function update(UpdateUserRequest $request, int $id)
    {
        $user = User::findOrFail($id);
        $userAction = new UpdateUserAction;
        $user = $userAction->execute($request, $user);

        return $user;
    }

    public function changePassword(ChangePasswordRequest $request, int $id)
    {
        $user = User::findOrFail($id);

        $changePasswordAction = new ChangePasswordAction;
        $changePasswordAction->execute($request, $user);

        return $user;
    }

    public function updateProfilePicture(UpdateProfilePictureRequest $request, int $id)
    {
        $user = User::findOrFail($id);

        $updateProfilePictureAction = new UpdateProfilePictureAction;
        $updateProfilePictureAction->execute($request, $user);

        return $user;
    }

    public function updateNotification(int $id)
    {
        $readAllNotificationAction = new ReadAllNotificationAction;
        $notification = $readAllNotificationAction->execute($id);

        return $notification;
    }

    public function delete(int $id)
    {
        $user = User::findOrFail($id);

        $userAction = new DeleteUserAction;

        return $userAction->execute($user, auth('api')->id());
    }

    public function resendVerificationEmail(int $userId)
    {
        $user = User::find($userId);
        if (! $user) {
            throw new Exception(__('api-response.error.user_not_found'), JsonResponse::HTTP_NOT_FOUND);
        }

        $emailResendSuccess = RateLimiter::attempt('resend-verify-email:'.$userId, $perThirtySeconds = 1, function () use ($user) {
            Mail::to($user->email)->send(new MailUserRegistered($user));
        },
            $decaySeconds = 30
        );

        if (! $emailResendSuccess) {
            $seconds = RateLimiter::availableIn('resend-verify-email:'.$userId);
            throw new Exception(__('api-response.error.try_resend_later', ['interval' => $seconds]), JsonResponse::HTTP_TOO_MANY_REQUESTS);
        }

        return $user;
    }
}
