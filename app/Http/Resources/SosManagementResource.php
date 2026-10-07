<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Enums\User\Gender;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class SosManagementResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request)
    {
        $code = match ($this->user_action_request) {
            'Call Ambulance' => 1,
            'Call Police' => 2,
            default => null,
        };

        $code_name = match ($this->user_action_request) {
            'Call Ambulance' => __('app.ambulance'),
            'Call Police' => __('app.police'),
            default => null,
        };

        $unit = $this->unit;
        $creator = $this->createdBy;

        $address = sprintf(
            'Unit Number: %s Street: %s Floor: %s Block: %s',
            data_get($unit, 'unit_number', '-'),
            data_get($unit, 'street', '-'),
            data_get($unit, 'floor', '-'),
            data_get($unit, 'block', '-')
        );

        $residence_detail = [
            'residence' => data_get($unit->residence, 'name'),
            'name' => data_get($creator, 'name'),
            'address' => $address,
            'unit_number' => data_get($unit, 'unit_number', '-'),
            'street' => data_get($unit, 'street', '-'),
            'floor' => data_get($unit, 'floor', '-'),
            'block' => data_get($unit, 'block', '-'),
            'age' => $creator?->date_of_birth ? Carbon::parse($creator->date_of_birth)->age : 0,
            'gender' => optional(Gender::tryFrom($creator?->gender))->label(),
        ];

        return [
            'id' => $this->id,
            'residence_detail' => $residence_detail,
            'code' => $code,
            'code_name' => $code_name,
            'remark' => $this->remark ?? null,
            'created_by' => $creator ? $creator?->name : null,
            'accepted_by' => $this->acceptedBy ? (int) $this->acceptedBy?->name : null,
            'created_at' => Carbon::parse($this->created_at)->setTimezone('Asia/Bangkok')->format('Y-m-d H:i:s'),
        ];
    }
}
