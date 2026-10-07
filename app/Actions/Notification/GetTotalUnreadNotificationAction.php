<?php

namespace App\Actions\Notification;

use App\Enums\User\Role;
use App\Exceptions\GeneralException;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetTotalUnreadNotificationAction
{
    public function execute(Request $request)
    {
        return $this->getNotification($request);
    }

    public function getNotification($request)
    {
        $totalUnreadNotification = 0;

        $allowedRoles = [
            Role::PROPERTY_MANAGEMENT,
            Role::UNIT_OWNER,
            Role::UNIT_TENANT,
            Role::DEVELOPER,
        ];

        $user = User::findOrFail($request->user_id);

        if (empty($user) == false) {
            $userRoles = $user->roles->pluck('id')->toArray();

            if (empty(array_intersect($userRoles, $allowedRoles))) {
                throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Unauthorized Access!');
            }

            $countUnreadNotificationAction = new CountUnreadNotificationAction;
            $totalUnreadNotification = $countUnreadNotificationAction->execute($request->user_id, $request->unit_id ?? null, $request->module ?? null);
        } else {
            throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'User Not Exist!');
        }

        return $totalUnreadNotification;
    }
}
