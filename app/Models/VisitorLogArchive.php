<?php

namespace App\Models;

use App\Enums\Visitor\EstampByType;
use App\Enums\Visitor\EstampStatus;
use App\Enums\Visitor\VisitingArrangementStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class VisitorLogArchive extends Model implements HasMedia
{
    use InteractsWithMedia, SoftDeletes;

    protected $table = 'visitor_logs_archive';

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
    ];

    protected $appends = [
        'estamp_status',
    ];

    protected $casts = [
        'arrival_time' => 'datetime',
        'leave_time' => 'datetime',
        'is_allowed' => 'boolean',
        'is_pre_register' => 'boolean',
        'vehicle_info' => 'array',
    ];

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(Visitor::class);
    }

    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    public function preregisterVisitor(): BelongsTo
    {
        return $this->belongsTo(PreregisterVisitor::class, 'visitor_id', 'visitor_id');
    }

    public function visitorCard(): BelongsTo
    {
        return $this->belongsTo(VisitorCard::class);
    }

    public function courierLogisticPartner(): BelongsTo
    {
        return $this->belongsTo(LogisticPartner::class, 'courier_logistic_partner_id');
    }

    public function foodDeliveryLogisticPartner(): BelongsTo
    {
        return $this->belongsTo(LogisticPartner::class, 'food_delivery_logistic_partner_id');
    }

    public function visitingArrangements(): HasMany
    {
        return $this->hasMany(VisitingArrangementArchive::class, 'visitor_log_id');
    }

    public function visitorParking(): HasOne
    {
        return $this->hasOne(VisitorParkingArchive::class, 'visitor_log_id');
    }

    public function originalMedias()
    {
        return $this->hasMany(Media::class, 'model_id')
            ->where('model_type', VisitorLog::class);
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
     * Get the estamp status from visiting arrangements.
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
}
