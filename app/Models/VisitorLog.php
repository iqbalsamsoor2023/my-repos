<?php

namespace App\Models;

use App\Enums\Visitor\ArrivalType;
use App\Enums\Visitor\EstampByType;
use App\Enums\Visitor\EstampStatus;
use App\Enums\Visitor\VehicleType;
use App\Enums\Visitor\VisitingArrangementStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class VisitorLog extends BaseModel implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'id',
        'visitor_id',
        'residence_id',
        'visitor_card_id',
        'visitor_purpose',
        'courier_logistic_partner_id',
        'food_delivery_logistic_partner_id',
        'visitor_code',
        'visitor_generated_no',
        'company_name',
        'arrival_type',
        'vehicle_type',
        'vehicle_plate_no',
        'arrival_time',
        'leave_time',
        'temperature',
        'passenger_count',
        'remark',
        'blacklist_remark',
        'is_allowed',
        'is_pre_register',
        'vehicle_info',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $appends = [
        'qr_code_url',
        'pdpa_status',
        'qr_visibility',
        'estamp_status',
        'esign_image_url',
    ];

    protected $casts = [
        'vehicle_info' => 'array',
    ];

    /**
     * Get the visitor that owns the VisitorLog.
     *
     * @return BelongsTo
     */
    public function visitor(): BelongsTo
    {
        return $this->belongsTo(Visitor::class);
    }

    /**
     * Get the residence that owns the VisitorLog.
     *
     * @return BelongsTo
     */
    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    /**
     * Get the visitor that comes from pre register visitor
     *
     * @return BelongsTo
     */
    public function preregisterVisitor(): BelongsTo
    {
        return $this->belongsTo(PreregisterVisitor::class, 'visitor_code', 'visitor_code');
    }

    /**
     * Get the visitor card that owns the VisitorLog.
     *
     * @return BelongsTo
     */
    public function visitorCard(): BelongsTo
    {
        return $this->belongsTo(VisitorCard::class);
    }

    /**
     * Get the courier logistic partner that owns the VisitorLog.
     *
     * @return BelongsTo
     */
    public function courierLogisticPartner(): BelongsTo
    {
        return $this->belongsTo(LogisticPartner::class, 'courier_logistic_partner_id');
    }

    /**
     * Get the food delivery logistic partner that owns the VisitorLog.
     *
     * @return BelongsTo
     */
    public function foodDeliveryLogisticPartner(): BelongsTo
    {
        return $this->belongsTo(LogisticPartner::class, 'food_delivery_logistic_partner_id');
    }

    /**
     * Get the visiting arrangements that owns the VisitorLog.
     *
     * @return HasMany
     */
    public function visitingArrangements(): HasMany
    {
        return $this->hasMany(VisitingArrangement::class);
    }

    /**
     * Get the visitor parking that owns the VisitorLog.
     *
     * @return HasOne
     */
    public function visitorParking(): HasOne
    {
        return $this->hasOne(VisitorParking::class);
    }

    /**
     * Interact with the user's first name.
     *
     * @return Attribute
     */
    public function estampStatus(): Attribute
    {
        return Attribute::make(
            get: function () {
                $arrangements = $this->visitingArrangements;

                if ($arrangements->isEmpty()) {
                    $status = EstampStatus::PENDING;
                } else {
                    $groupedByUnit = $arrangements
                        ->groupBy('unit_id')
                        ->map(fn($arrangements) => [
                            'statuses' => $arrangements->pluck('status')->toArray(),
                            'feedbacks' => $arrangements->pluck('feedback_remark')->toArray(),
                        ]);

                    $statuses = $arrangements
                        ->pluck('status')
                        ->filter()
                        ->toArray();

                    if (
                        in_array(VisitingArrangementStatus::NOT_MY_VISITOR->value, $statuses) &&
                        !in_array(VisitingArrangementStatus::MY_VISITOR->value, $statuses)
                    ) {
                        $status = EstampStatus::NOT_MY_VISITOR;
                    } elseif (in_array(VisitingArrangementStatus::STAMP_BY_PM->value, $statuses)) {
                        $status = EstampStatus::STAMP_BY_PM;
                    } else {
                        $stampedByPM = $arrangements->contains(function ($arrangement) {
                            return $arrangement->status === VisitingArrangementStatus::MY_VISITOR->value &&
                                $arrangement->estamp_by_type === EstampByType::PM->value;
                        });

                        if ($stampedByPM) {
                            $status = EstampStatus::STAMP_BY_PM;
                        } else {
                            $stampedByResident = $arrangements->contains(function ($arrangement) {
                                return $arrangement->status === VisitingArrangementStatus::MY_VISITOR->value &&
                                    $arrangement->estamp_by_type === EstampByType::RESIDENT->value;
                            });

                            if ($stampedByResident) {
                                $status = EstampStatus::ESTAMP;
                            } else {
                                $allFeedbacksProvided = collect($groupedByUnit)->every(function ($data) {
                                    return !in_array(null, $data['feedbacks'], true) &&
                                        !in_array('', $data['feedbacks'], true);
                                });

                                if ($allFeedbacksProvided) {
                                    $status = EstampStatus::CANCEL_BY_SG;
                                } else {
                                    $status = EstampStatus::PENDING;
                                }
                            }
                        }
                    }
                }

                return [
                    'value' => $status->getValue(),
                    'label' => $status->getLabel(),
                ];
            }
        );
    }

    /**
     * Interact with the qr code's url.
     *
     * @return Attribute
     */
    public function qrCodeUrl(): Attribute
    {
        return Attribute::make(
            get: fn() => route('visitors.qr', ['visitor_code' => $this->attributes['visitor_code']]),
        );
    }

    /**
     * Interact with the esign's image url.
     *
     * @return Attribute
     */
    protected function esignImageUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                $media = $this->getRelationValue('media');
    
                if (! $media) {
                    return null;
                }
    
                return optional(
                    $media->firstWhere('collection_name', 'esign_image')
                )->getFullUrl();
            }
        );
    }

    /**
     * Get the visitor's status.
     *
     * @return Attribute
     */
    protected function status(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->arrival_time != null && $this->leave_time == null) {
                    return 'arrive';
                } elseif ($this->arrival_time != null && $this->leave_time != null) {
                    return 'depart';
                } else {
                    return null;
                }
            }
        );
    }

    /**
     * Interact with the vehicle type enum.
     *
     * @param  string  $value
     * @return Attribute
     */
    public function ArrivalTypes(): Attribute
    {
        return Attribute::make(
            get: function () {
                switch ($this->arrival_type) {
                    case ArrivalType::DRIVE_IN->value:
                        return str_replace('_', ' ', Str::title(ArrivalType::DRIVE_IN->name));
                        break;
                    case ArrivalType::WALK_IN->value:
                        return str_replace('_', ' ', Str::title(ArrivalType::WALK_IN->name));
                        break;
                    default:
                        return null;
                }
            }
        );
    }

    /**
     * Interact with the vehicle type enum.
     *
     * @param  string  $value
     * @return Attribute
     */
    public function vehicleTypes(): Attribute
    {
        return Attribute::make(
            get: function () {
                switch ($this->vehicle_type) {
                    case VehicleType::CAR->value:
                        return Str::title(VehicleType::CAR->name);
                        break;
                    case VehicleType::TRUCK->value:
                        return Str::title(VehicleType::TRUCK->name);
                        break;
                    case VehicleType::MOTORBIKE->value:
                        return Str::title(VehicleType::MOTORBIKE->name);
                        break;
                    case VehicleType::VAN->value:
                        return Str::title(VehicleType::VAN->name);
                        break;
                    case VehicleType::TAXI->value:
                        return Str::title(VehicleType::TAXI->name);
                        break;
                    case VehicleType::PICKUP->value:
                        return Str::title(VehicleType::PICKUP->name);
                        break;
                    default:
                        return null;
                }
            }
        );
    }

    /**
     * Get the visitor's pdpa status.
     *
     * @return Attribute
     */
    protected function pdpaStatus(): Attribute
    {
        return Attribute::make(
            get: function () {
                $hasMedia = $this->media
                    ->where('collection_name', 'esign_image')
                    ->isNotEmpty();

                return $hasMedia ? 'accepted' : 'none';
            }
        );
    }

    /**
     * Get the visitor's qr visibility status.
     *
     * @return Attribute
     */
    protected function qrVisibility(): Attribute
    {
        return Attribute::make(
            get: function () {
                // Try to use already-loaded relationship to avoid N+1 queries
                if ($this->relationLoaded('visitingArrangements')) {
                    $arrangement = $this->visitingArrangements->first();
                    if ($arrangement && $arrangement->relationLoaded('residence') && $arrangement->residence) {
                        $residence = $arrangement->residence;
                        if ($residence->relationLoaded('visitorSetting') && $residence->visitorSetting) {
                            return $residence->visitorSetting->is_qr_active ?? 0;
                        }
                    }
                }

                // Fallback: query directly (for API/GraphQL where relationships may not be loaded)
                $residenceId = $this->visitingArrangements
                    ->pluck('residence_id')
                    ->first();

                if (! $residenceId) {
                    return 0;
                }

                return VisitorSetting::where('residence_id', $residenceId)
                    ->value('is_qr_active') ?? 0;
            }
        );
    }

    /**
     * Watermark conversion for visitor image
     *
     * @param  Media  $media
     */
    // public function registerMediaConversions(Media $media = null): void
    // {
    //     if ($media->getCustomProperty('type') == 'id_image' || $media->getCustomProperty('type') == 'visitor_image' || $media->getCustomProperty('type') == 'vehicle_image') {
    //         $this->addMediaConversion('watermark')
    //             ->width(2000)
    //             ->height(2000)
    //             ->watermark(public_path('images/mymooban-watermark.png'))
    //             ->watermarkOpacity(50)
    //             ->watermarkPosition(Manipulations::POSITION_CENTER)
    //             ->watermarkHeight(50, Manipulations::UNIT_PERCENT)
    //             ->watermarkWidth(100, Manipulations::UNIT_PERCENT)
    //             ->watermarkFit(Manipulations::FIT_STRETCH)
    //             ->keepOriginalImageFormat();
    //     }
    // }
}
