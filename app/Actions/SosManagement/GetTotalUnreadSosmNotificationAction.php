<?php

namespace App\Actions\SosManagement;

use App\Actions\Notification\CountUnreadNotificationAction;
use App\Enums\User\Role;
use App\Exceptions\GeneralException;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetTotalUnreadSosmNotificationAction
{
    public function execute(Request $request)
    {
        return $this->getNotification($request);
    }

    public function getNotification($request)
    {
        $allowedRoles = [
            Role::PROPERTY_MANAGEMENT,
            Role::UNIT_OWNER,
            Role::UNIT_TENANT,
        ];

        $user = User::findOrFail($request->user_id);

        if (empty($user) == false) {
            foreach ($user->roles as $role) {
                if (in_array($role->id, $allowedRoles) == false) {
                    throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Unauthorized Access!');
                }
            }

            $countUnreadNotificationAction = new CountUnreadNotificationAction;
            $total_unread_notification = $countUnreadNotificationAction->execute($request->user_id, $request->unit_id ?? null, $request->module ?? null);
        } else {
            throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'User Not Exist!');
        }

        return $total_unread_notification;
    }
}
