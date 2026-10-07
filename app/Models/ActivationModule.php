<?php

namespace App\Models;

use App\Enums\ActivationModule\ModuleType;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ActivationModule extends Model
{
    use Filterable,HasFactory,SoftDeletes;

    protected $fillable = [
        'residence_id',
        'module',
        'module_type',
        'is_active',
        'created_at',
        'updated_at',
    ];

    /**
     * Get the residence that owns the ActivationModule.
     *
     * @return BelongsTo
     */
    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    /**
     * Interact with the activation module name.
     *
     * @param  string  $value
     * @return Attribute
     */
    public function moduleTypeName(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if ($this->module_type == ModuleType::INBOX->value) {
                    return __(ucfirst(strtolower(ModuleType::INBOX->name)));
                } elseif ($this->module_type == ModuleType::VISITOR->value) {
                    return __(ucfirst(strtolower(ModuleType::VISITOR->name)));
                } elseif ($this->module_type == ModuleType::CLAIM->value) {
                    return __(ucfirst(strtolower(ModuleType::CLAIM->name)));
                } elseif ($this->module_type == ModuleType::PARCEL->value) {
                    return __(ucfirst(strtolower(ModuleType::PARCEL->name)));
                } elseif ($this->module_type == ModuleType::BOOKING->value) {
                    return __(ucfirst(strtolower(ModuleType::BOOKING->name)));
                } elseif ($this->module_type == ModuleType::DEVELOPER->value) {
                    return __(ucfirst(strtolower(ModuleType::DEVELOPER->name)));
                } elseif ($this->module_type == ModuleType::CONTACT->value) {
                    return __(ucfirst(strtolower(ModuleType::CONTACT->name)));
                } elseif ($this->module_type == ModuleType::BILLING->value) {
                    return __(ucfirst(strtolower(ModuleType::BILLING->name)));
                } elseif ($this->module_type == ModuleType::BRAND->value) {
                    return __(ucfirst(strtolower(ModuleType::BRAND->name)));
                } elseif ($this->module_type == ModuleType::PURPOSE_OF_VISIT->value) {
                    return __(Str::headline(ucfirst(strtolower(ModuleType::PURPOSE_OF_VISIT->name))));
                } elseif ($this->module_type == ModuleType::CONTACT_NUMBER->value) {
                    return __(Str::headline(ucfirst(strtolower(ModuleType::CONTACT_NUMBER->name))));
                } elseif ($this->module_type == ModuleType::TEMPERATURE->value) {
                    return __(ucfirst(strtolower(ModuleType::TEMPERATURE->name)));
                } elseif ($this->module_type == ModuleType::COMPANY_NAME->value) {
                    return __(Str::headline(ucfirst(strtolower(ModuleType::COMPANY_NAME->name))));
                } elseif ($this->module_type == ModuleType::PASSENGER->value) {
                    return __(ucfirst(strtolower(ModuleType::PASSENGER->name)));
                } elseif ($this->module_type == ModuleType::REMARK->value) {
                    return __(ucfirst(strtolower(ModuleType::REMARK->name)));
                } elseif ($this->module_type == ModuleType::COLOR->value) {
                    return __(ucfirst(strtolower(ModuleType::COLOR->name)));
                } elseif ($this->module_type == ModuleType::PDPA->value) {
                    return ModuleType::PDPA->name;
                } elseif ($this->module_type == ModuleType::VISITOR_PHOTO->value) {
                    return __(Str::headline(ucfirst(strtolower(ModuleType::VISITOR_PHOTO->name))));
                } elseif ($this->module_type == ModuleType::SCAN_VISITOR_CARD->value) {
                    return __(Str::headline(ucfirst(strtolower(ModuleType::SCAN_VISITOR_CARD->name))));
                } elseif ($this->module_type == ModuleType::VEHICLE_PHOTO->value) {
                    return __(Str::headline(ucfirst(strtolower(ModuleType::VEHICLE_PHOTO->name))));
                } elseif ($this->module_type == ModuleType::FOOD_AND_PARCEL->value) {
                    return __(Str::headline(ucfirst(strtolower(ModuleType::FOOD_AND_PARCEL->name))));
                } elseif ($this->module_type == ModuleType::PARKING->value) {
                    return __(ucfirst(strtolower(ModuleType::PARKING->name)));
                } elseif ($this->module_type == ModuleType::VS_VISITOR_CARD->value) {
                    return __(substr(Str::headline(ucfirst(strtolower(ModuleType::VS_VISITOR_CARD->name))), 3));
                } elseif ($this->module_type == ModuleType::VS_VISITOR_NAME->value) {
                    return __(substr(Str::headline(ucfirst(strtolower(ModuleType::VS_VISITOR_NAME->name))), 3));
                } elseif ($this->module_type == ModuleType::VS_VEHICLE_PLATE_NO->value) {
                    return __(substr(Str::headline(ucfirst(strtolower(ModuleType::VS_VEHICLE_PLATE_NO->name))), 3));
                } elseif ($this->module_type == ModuleType::VS_PROVINCE_OF_VEHICLE->value) {
                    return __(substr(Str::headline(ucfirst(strtolower(ModuleType::VS_PROVINCE_OF_VEHICLE->name))), 3));
                } elseif ($this->module_type == ModuleType::VS_BRAND->value) {
                    return __(substr(Str::headline(ucfirst(strtolower(ModuleType::VS_BRAND->name))), 3));
                } elseif ($this->module_type == ModuleType::VS_COLOR->value) {
                    return __(substr(Str::headline(ucfirst(strtolower(ModuleType::VS_COLOR->name))), 3));
                } elseif ($this->module_type == ModuleType::VS_PURPOSE_OF_VISIT->value) {
                    return __(substr(Str::headline(ucfirst(strtolower(ModuleType::VS_PURPOSE_OF_VISIT->name))), 3));
                } elseif ($this->module_type == ModuleType::VS_VEHICLE_TYPE->value) {
                    return __(substr(Str::headline(ucfirst(strtolower(ModuleType::VS_VEHICLE_TYPE->name))), 3));
                } elseif ($this->module_type == ModuleType::VS_CONTACT_AT->value) {
                    return __(substr(Str::headline(ucfirst(strtolower(ModuleType::VS_CONTACT_AT->name))), 3));
                } elseif ($this->module_type == ModuleType::VS_CONTACT_NUMBER->value) {
                    return __(substr(Str::headline(ucfirst(strtolower(ModuleType::VS_CONTACT_NUMBER->name))), 3));
                } elseif ($this->module_type == ModuleType::VS_TEMPERATURE->value) {
                    return __(substr(Str::headline(ucfirst(strtolower(ModuleType::VS_TEMPERATURE->name))), 3));
                } elseif ($this->module_type == ModuleType::VS_COMPANY_NAME->value) {
                    return __(substr(Str::headline(ucfirst(strtolower(ModuleType::VS_COMPANY_NAME->name))), 3));
                } elseif ($this->module_type == ModuleType::VS_PASSENGER->value) {
                    return __(substr(Str::headline(ucfirst(strtolower(ModuleType::VS_PASSENGER->name))), 3));
                } elseif ($this->module_type == ModuleType::VS_REMARK->value) {
                    return __(substr(Str::headline(ucfirst(strtolower(ModuleType::VS_REMARK->name))), 3));
                } elseif ($this->module_type == ModuleType::VS_STAMP->value) {
                    return __(substr(Str::headline(ucfirst(strtolower(ModuleType::VS_STAMP->name))), 3));
                } elseif ($this->module_type == ModuleType::VS_QR_SCAN_OUT->value) {
                    return __(substr(Str::headline(ucfirst(strtolower(ModuleType::VS_QR_SCAN_OUT->name))), 3));
                } elseif ($this->module_type == ModuleType::VS_MOOBAN_LOGO->value) {
                    return __(substr(Str::headline(ucfirst(strtolower(ModuleType::VS_MOOBAN_LOGO->name))), 3));
                } elseif ($this->module_type == ModuleType::PARKING_FEE->value) {
                    return __(Str::headline(ucfirst(strtolower(ModuleType::PARKING_FEE->name))));
                } elseif ($this->module_type == ModuleType::VS_ONLY_STAMP->value) {
                    return 'Only Stamp';
                } elseif ($this->module_type == ModuleType::VS_ONLY_SIGNATURE->value) {
                    return 'Only Signature';
                } else {
                    return '';
                }
            }
        );
    }
}
