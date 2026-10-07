<?php

namespace App\Http\Requests\InsuranceCompany;

use Illuminate\Contracts\Validation\ValidationRule;
use App\Enums\Company\InsuranceTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class GetInsuranceCompanyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['nullable', new Enum(InsuranceTypeEnum::class)],
        ];
    }
}
