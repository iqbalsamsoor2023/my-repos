<?php

namespace App\Http\Requests\UnitTenant;

use App\Enums\UserFamily\Relationship;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUnitTenantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'relationship' => 'required|integer|in:'.Relationship::HUSBAND->value.','.Relationship::WIFE->value.','
                                                    .Relationship::FATHER->value.','.Relationship::MOTHER->value.','
                                                    .Relationship::BROTHER->value.','.Relationship::SISTER->value.','
                                                    .Relationship::SON->value.','.Relationship::DAUGHTER->value.','
                                                    .Relationship::RELATIVE->value.','.Relationship::CO_HOME->value,
        ];
    }
}
