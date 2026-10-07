<?php

namespace App\Models;

use App\Enums\Visitor\EstampByType;
use App\Enums\Visitor\VisitingArrangementStatus;
use App\Models\Sgoc\User as SgocUser;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class VisitingArrangement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'visitor_log_id',
        'residence_id',
        'unit_id',
        'user_id',
        'status',
        'estamp_by',
        'estamp_by_type',
        'feedback_remark',
        'created_at',
        'updated_at',
    ];

    protected $appends = [
        'stamp_by_name',
        'estamp_status',
        'user_estamp_status',
    ];

    /**
     * Get the visitor log that owns the VisitingArrangement.
     *
     * @return BelongsTo
     */
    public function visitorLog(): BelongsTo
    {
        return $this->belongsTo(VisitorLog::class);
    }

    /**
     * Get the residence that owns the VisitingArrangement.
     *
     * @return BelongsTo
     */
    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    /**
     * Get the unit that owns the VisitingArrangement.
     *
     * @return BelongsTo
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Get the user that owns the VisitingArrangement.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user that stamped the VisitingArrangement.
     *
     * @return BelongsTo
     */
    public function estampBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'estamp_by', 'id');
    }

    /**
     * Get the visitor remarks.
     *
     * @return HasMany
     */
    public function visitorRemarks(): HasMany
    {
        return $this->hasMany(VisitorRemark::class, 'residence_id', 'residence_id');
    }

    /**
     * Interact with the user's first name.
     *
     * @return Attribute
     */
    protected function stampByName(): Attribute
    {
        if (($this->status == VisitingArrangementStatus::CANCEL_BY_SG->value) && (is_null($this->estamp_by) == false)) {
            $sgocUser = SgocUser::whereId($this->estamp_by)->first();

            return Attribute::make(
                get: fn ($value) => data_get($sgocUser, 'name', null),
            );
        }
        $user = User::find($this->estamp_by);

        return Attribute::make(
            get: fn ($value) => data_get($user, 'name', null),
        );
    }

    /**
     * Interact with the visiting arrangements status.
     *
     * @return Attribute
     */
    protected function statusName(): Attribute
    {
        if ($this->status === VisitingArrangementStatus::MY_VISITOR->value) {
            $status = 'My Visitor';
        } elseif ($this->status === VisitingArrangementStatus::NOT_MY_VISITOR->value) {
            $status = 'Not My Visitor';
        } elseif ($this->status === VisitingArrangementStatus::CANCEL_BY_SG->value) {
            $status = 'Cancel by SG';
            // } elseif ($this->status === VisitingArrangementStatus::BLACKLISTED->value) {
            //     $status = 'Blacklisted';
        }

        return Attribute::make(
            get: fn ($value) => $status ?? null,
        );
    }

    /**
     * Interact with the user's estamp status for user app.
     *
     * @return Attribute
     */
    protected function userEstampStatus(): ?Attribute
    {
        $request = request()->input();
        $lang = request()->header('Accept-Language');

        return Attribute::make(
            get: function () use ($lang, $request) {
                $user_estamp_status = null;

                if (isset($request['unit_id'])) {
                    if ($request['unit_id'] == $this->unit_id && $this->estamp_by_type == EstampByType::RESIDENT->value && $this->status == VisitingArrangementStatus::MY_VISITOR->value) {
                        $stamp_name = isset($this->estampBy) ? $this->estampBy->name : '-';
                        $stamp_by = $lang == 'th' ? 'ประทับตราโดย ' : 'Stamp by ';
                        $user_estamp_status = $stamp_by.$stamp_name;
                    } elseif ($this->estamp_by_type == EStampByType::PM->value) {
                        $user_estamp_status = $lang == 'th' ? 'ประทับตราโดยนิติ' : 'Stamp by PM';
                    } elseif ($this->status == VisitingArrangementStatus::NOT_MY_VISITOR->value) {
                        $name = isset($this->estampBy) ? $this->estampBy->name : '-';
                        $not_my_visitor = $lang == 'th' ? 'ไม่ใช่ผู้มาติดต่อ ' : 'Not my visitor by ';
                        $user_estamp_status = $not_my_visitor.$name;
                    } elseif ($this->status == VisitingArrangementStatus::CANCEL_BY_SG->value) {
                        $user_estamp_status = $lang == 'th' ? 'ยกเลิกโดย รปภ.' : 'Cancel by security guard';
                        // } elseif ($this->status == VisitingArrangementStatus::BLACKLISTED->value) {
                        //     $user_estamp_status = $lang == 'th' ? 'รายชื่อห้ามเข้า' : 'Blacklisted';
                    } else {
                        $visited_units = VisitingArrangement::where('visitor_log_id', $this->visitor_log_id)->whereNotNull('status')->get();
                        if (count($visited_units) > 0) {
                            $user_estamp_status = $lang == 'th' ? 'กำลังรอ อี-สแตมป์ | มีบ้านหลังอื่นประทับตรา' : 'Partial e-stamp | Waiting e-stamp';
                        } else {
                            $user_estamp_status = $lang == 'th' ? 'กำลังรอ อี-แสตมป์' : 'Waiting e-stamp';
                        }
                    }
                }

                return $user_estamp_status;
            }
        );
    }

    protected function estampStatus(): ?Attribute
    {
        return Attribute::make(
            get: function () {
                $lang = app()->getLocale();
                $estampStatus = match ($this->status) {
                    VisitingArrangementStatus::MY_VISITOR->value => $this->getMyVisitorStatus($lang),
                    VisitingArrangementStatus::NOT_MY_VISITOR->value => $this->getNotMyVisitorStatus($lang),
                    VisitingArrangementStatus::CANCEL_BY_SG->value => $lang == 'th' ? 'ยกเลิกโดยรปภ.' : 'Cancel by security guard',
                    // VisitingArrangementStatus::BLACKLISTED->value => $lang == 'th' ? 'รายชื่อห้ามเข้า' : 'Blacklisted', // Deprecated
                    default => $lang == 'th' ? 'รอประทับตรา' : 'Pending',
                };

                return $estampStatus;
            }
        );
    }

    private function getMyVisitorStatus($lang): string
    {
        if ($this->estamp_by_type == EStampByType::RESIDENT->value) {
            $stampBy = $lang == 'th' ? 'ประทับตราโดย ' : 'Stamp by ';

            return $stampBy.($this->estampBy?->name ?? '-');
        }

        return $lang == 'th' ? 'ประทับตราโดยนิติฯ' : 'Stamp by PM';
    }

    private function getNotMyVisitorStatus($lang): string
    {
        $name = $this->estampBy?->name ?? '-';

        return $lang == 'th' ? "ไม่ใช่ผู้มาติดต่อ $name" : "Not my visitor by $name";
    }
}
