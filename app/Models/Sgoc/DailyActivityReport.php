<?php

namespace App\Models\Sgoc;

use App\Models\Residence;
use App\Models\SgocMedia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class DailyActivityReport extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $connection = 'sgoc';

    protected $table = 'daily_activity_reports';

    public function __construct()
    {
        $this->table = DB::connection($this->connection)->getDatabaseName().'.'.$this->getTable();
    }

    protected $casts = [
        'examined_person_names' => 'array',
        'reports' => 'array',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    /**
     * Get the residence associated with the daily activity report.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function residence()
    {
        return $this->belongsTo(Residence::class, 'residence_id', 'id');
    }

     /**
     * Get the user associated with the daily activity report.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function reportedBy()
    {
        return $this->belongsTo(User::class, 'reported_by', 'id');
    }

    /**
     * Get the shift type associated with the daily activity report.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function shiftType()
    {
        return $this->belongsTo(ShiftType::class);
    }

    /**
     * Get the report category item associated with the daily activity report.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function reportCategoryItem()
    {
        return $this->belongsTo(ReportCategoryItem::class);
    }

    /**
     * Get the mediable model.
     *
     * @return MorphMany
     */
    public function media(): MorphMany
    {
        return $this->morphMany(SgocMedia::class, 'model');
    }
}
