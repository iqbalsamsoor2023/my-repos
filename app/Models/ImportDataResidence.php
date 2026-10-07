<?php

namespace App\Models;

use App\Events\ResidenceCreated;
use App\Models\Erp\ThailandSubDistrict;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ImportDataResidence extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $table = 'residences';

    protected $fillable = [
        'id',
        'name',
        'name_th',
        'type',
        'completion_year',
        'latitude',
        'longitude',
        'subdistrict_id',
        'developer_id',
        'developer_user_id',
        'property_management_type',
        'property_management_id',
        'property_management_user_id',
        'guard_talk_user_id',
        'receptionist_user_id',
        'accountant_user_id',
        'sgoc_company_id',
        'sgoc_residence_guard_user_id',
        'insurance_company_id',
        'company_id',
        'is_active',
        'is_demo',
        'subscription_start_date',
        'subscription_end_date',
        'support_ticket_status',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * The event map for the model.
     *
     * @var array
     */
    protected $dispatchesEvents = [
        'created' => ResidenceCreated::class,
    ];

    /**
     * Get the residence company that owns the Residence.
     *
     * @return BelongsTo
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    /**
     * Get the developer company that owns the Residence.
     *
     * @return BelongsTo
     */
    public function developer(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'developer_id', 'id');
    }

    /**
     * Get the developer user that owns the Residence.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BenlongsTo
     */
    public function developerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'developer_user_id', 'id');
    }

    /**
     * Get the property management company that owns the Residence.
     *
     * @return BelongsTo
     */
    public function propertyManagement(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'property_management_id', 'id');
    }

    /**
     * Get the property management user that owns the Residence.
     *
     * @return BelongsTo
     */
    public function propertyManagementUser(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the guard talk user that owns the Residence.
     *
     * @return BelongsTo
     */
    public function guardTalkUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guard_talk_user_id', 'id');
    }

    /**
     * Get the property management company that owns the Residence.
     *
     * @return BelongsTo
     */
    public function InsuranceCompany(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompany::class);
    }

    /**
     * Get the receptionist user in the Residence.
     *
     * @return BelongsTo
     */
    public function receptionist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receptionist_user_id', 'id');
    }

    /**
     * Get the accountant user in the Residence.
     *
     * @return BelongsTo
     */
    public function accountant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accountant_user_id', 'id');
    }

    /**
     * Get the province that owns the Company.
     *
     * @return BelongsTo
     */
    public function subdistrict(): BelongsTo
    {
        return $this->belongsTo(ThailandSubDistrict::class);
    }

    /**
     * Get the units that owns the Residence.
     *
     * @return HasMany
     */
    public function units(): HasMany
    {
        return $this->HasMany(Unit::class);
    }

    /**
     * Get the announcements that owns the Residence.
     *
     * @return HasMany
     */
    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class);
    }

    /**
     * Get the visitors that owns the Residence.
     *
     * @return HasMany
     */
    public function visitors(): HasMany
    {
        return $this->hasMany(Visitor::class);
    }

    /**
     * Get the visitor setting that owns the Residence.
     *
     * @return HasOne
     */
    public function visitorSetting(): HasOne
    {
        return $this->hasOne(VisitorSetting::class);
    }

    /**
     * Get the visitor cards that owns the Residence.
     *
     * @return HasMany
     */
    public function visitorCards(): HasMany
    {
        return $this->hasMany(VisitorCard::class);
    }

    /**
     * Get the visitor purposes that owns the Residence.
     *
     * @return HasMany
     */
    public function visitorPurposes(): HasMany
    {
        return $this->hasMany(VisitorPurpose::class);
    }

    /**
     * Get the visitor remarks that owns the Residence.
     *
     * @return HasMany
     */
    public function visitorRemarks(): HasMany
    {
        return $this->hasMany(VisitorRemark::class);
    }

    /**
     * Get the blacklisted visitors that owns the Residence.
     *
     * @return HasMany
     */
    public function blacklistedVisitors(): HasMany
    {
        return $this->hasMany(BlacklistedVisitor::class);
    }

    /**
     * Get the events that owns the Residence.
     *
     * @return HasMany
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * Get the companies that owns the Residence.
     *
     * @return MorphToMany
     */
    public function companies(): MorphToMany
    {
        return $this->morphToMany(Company::class, 'companiable');
    }

    /**
     * Get the subscription expires that owns the Residence.
     *
     * @return HasMany
     */
    public function subscriptionExpires(): HasMany
    {
        return $this->hasMany(SubscriptionExpire::class);
    }

    /**
     * Get the activation modules that owns the Residence.
     *
     * @return HasMany
     */
    public function activationModules(): HasMany
    {
        return $this->hasMany(ActivationModule::class);
    }

    /**
     * Get parking in the Residence.
     *
     * @return HasOne
     */
    public function calculations(): HasOne
    {
        return $this->hasOne(Parking::class);
    }

    /**
     * Get the amenities that owns the Residence.
     *
     * @return HasMany
     */
    public function amenities(): HasMany
    {
        return $this->hasMany(Amenity::class);
    }

    /**
     * Get the other amenity that owns the Residence.
     *
     * @return HasOne
     */
    public function otherAmenity(): HasOne
    {
        return $this->hasOne(OtherAmenity::class);
    }

    /**
     * Accessor to get the residence age.
     *
     * @return Attribute
     */
    protected function getResidenceAgeAttribute(): string
    {
        $current_year = date('Y');

        if (! empty($this->completion_year)) {
            $age = $current_year - $this->completion_year;
            $year = ($age > 1) ? __('Years') : __('Year');
            $residence_age = ($age > 0) ? $age.' '.$year : __('Under Construction');
        } else {
            $residence_age = '-';
        }

        return $residence_age;
    }

    /**
     * Interact with the cover image url.
     *
     * @param  string  $value
     * @return Attribute
     */
    public function imageUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                $mediaItems = $this->getMedia('cover_image');
                $url = count($mediaItems) > 0 ? $mediaItems[0]->getFullUrl() : 'https://dashboard.mymooban.co.th/images/no-image.png';

                return $url;
            }
        );
    }
}
