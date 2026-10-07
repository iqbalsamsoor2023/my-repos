<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserHealthResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'blood_type' => $this->blood_type,
            'height' => $this->height,
            'weight' => $this->weight,
            'insurance_company_id' => $this?->user?->insurance_company_id,
            'insurance_policy_no' => $this?->user?->insurance_policy_no,
            'insurance_expiry_date' => $this?->user?->insurance_expiry_date,
            'health_questionnaire_answers' => $this->health_questionnaire_answers,
        ];
    }
}
