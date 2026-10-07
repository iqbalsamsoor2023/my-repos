<?php

namespace App\Http\Resources;

use App\Enums\UnitUser\ApprovalStatusType;
use App\Enums\UnitUser\UnitUserRoleType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserFamilyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        switch ($this->is_owner) {
            case UnitUserRoleType::OWNER->value:
                $type = __('user.owner');
                break;
            case UnitUserRoleType::TENANT->value:
                $type = __('user.tenant');
                break;
            default:
                $type = 'N/A';
        }

        $relationship = $this->relationship ?? 'N/A';

        return [
            'id' => $this->id,
            'type' => $type,
            'is_main_owner' => $this->is_main_owner,
            'is_main_tenant' => $this->is_main_tenant,
            'relationship' => $relationship,
            'resident_status' => $this->approval_status === ApprovalStatusType::APPROVED->value ? ApprovalStatusType::APPROVED->label() : ApprovalStatusType::REJECTED->label(),
            'is_created_via_family' => $this->is_created_via_family,
            'user' => $this->user ? [
                'id' => $this->user->id,
                'country_id' => $this->user->country_id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'id_number' => $this->user->id_number,
                'phone_no' => $this->user->phone_no,
                'date_of_birth' => optional($this->user->date_of_birth)->format('Y-m-d'),
                'gender' => $this->user->gender,
                'passport_number' => $this->user->passport_number,
                'passport_expiry' => $this->user->passport_expiry,
            ] : null,
        ];
    }
}
