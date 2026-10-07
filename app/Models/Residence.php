<?php

namespace App\Models;

use App\Enums\Residence\SubType;
use App\Enums\User\RoleType;
use App\Events\ResidenceCreated;
use App\Models\Erp\CdpCompany;
use App\Models\Erp\DigitalToolAllocation;
use App\Models\Erp\DtaDigitalTool;
use App\Models\Erp\ThailandSubDistrict;
use App\Models\Sgoc\Checkpoint;
use App\Models\Sgoc\PvrSite;
use App\Support\QueryGuardSupport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Residence extends BaseModel implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $connection = 'mysql';

    protected $table = 'residences';

    const CONDO_TYPE = [SubType::CONDO_LOW_RISE->value, SubType::CONDO_HIGH_RISE->value];

    protected $fillable = [
        'id',
        'name',
        'name_th',
        'mooban_type',
        'sub_type',
        'location_tags',
        'main_road',
        'full_address',
        'google_location_link',
        'completion_year',
        'latitude',
        'longitude',
        'subdistrict_id',
        'developer_id',
        'developer_user_id',
        'property_management_type',
        'property_management_id',
        'juristic_details',
        'person_in_charges',
        'property_management_user_id',
        'receptionist_user_id',
        'accountant_user_id',
        'sgoc_company_id',
        'sgoc_residence_guard_user_id',
        'security_guard_count',
        'residence_activation_status_id',
        'internet_provider_id',
        'guard_house_entry_number',
        'guard_house_lane_type',
        'insurance_company_id',
        'company_id',
        'sales_management_user_id',
        'rtm_user_id',
        'report_format',
        'bpo_software_suppliers',
        'subscription_start_date',
        'subscription_end_date',
        'support_ticket_status',
        'appointment_datetime_status',
        'verify_private_claim',
        'verify_public_claim',
        'has_roof',
        'entrance_barrier_type',
        'has_cctv',
        'cctv_count',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'internet_provider_id' => 'array',
        'juristic_details' => 'array',
        'person_in_charges' => 'array',
        'location_tags' => 'array',
        'bpo_software_suppliers' => 'array',
        'has_cctv' => 'boolean',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'image_url',
        'logo_url',
    ];

    /**
     * The event map for the model.
     *
     * @var array
     */
    protected $dispatchesEvents = [
        'created' => ResidenceCreated::class,
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->table = DB::connection($this->connection)->getDatabaseName().'.'.$this->getTable();
    }

    protected static function booted()
    {
        static::saving(function (Residence $residence) {
            if (! $residence->has_cctv) {
                $residence->cctv_count = 0;
            }
        });
    }

    public function scopeVisibleToUser(Builder $query, ?User $user): Builder
    {
        if (! $user) {
            return QueryGuardSupport::denyAll($query);
        }

        if ($user->hasRole(RoleType::DEVELOPER->value)) {
            $developerId = CdpCompany::query()
                ->where('mmb_user_id', $user->id)
                ->value('id');

            return $developerId
                ? $query->where('developer_id', $developerId)
                : QueryGuardSupport::denyAll($query);
        }

        if ($user->hasRole(RoleType::PROPERTY_MANAGEMENT->value)) {
            return $query->where('property_management_user_id', $user->id);
        }

        if ($user->hasRole(RoleType::PROPERTY_MANAGEMENT_OPERATION_CENTER->value)) {
            $companyId = CdpCompany::query()
                ->where('mmb_user_id', $user->id)
                ->value('id');

            return $companyId
                ? $query->where('property_management_id', $companyId)
                : QueryGuardSupport::denyAll($query);
        }

        if ($user->hasRole(RoleType::RESALES_AND_TENANCY_MANAGEMENT->value)) {
            return $query->where('rtm_user_id', $user->id);
        }

        if ($user->hasRole(RoleType::SALES_MANAGEMENT->value)) {
            return $query->where('sales_management_user_id', $user->id);
        }

        return $query;
    }

    /**
     * Get the residence company that owns the Residence.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    /**
     * Get the developer company that owns the Residence.
     */
    public function developer(): BelongsTo
    {
        return $this->belongsTo(CdpCompany::class, 'developer_id', 'id');
    }

    /**
     * Get the developer user that owns the Residence.
     */
    public function developerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'developer_user_id', 'id');
    }

    /**
     * Get the property management company that owns the Residence.
     */
    public function propertyManagement(): BelongsTo
    {
        return $this->belongsTo(CdpCompany::class, 'property_management_id', 'id');
    }

    /**
     * Get the property management user that owns the Residence.
     */
    public function propertyManagementUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'property_management_user_id', 'id');
    }

    /**
     * Get the residence guard user that owns the Residence.
     */
    public function residenceGuardUser(): BelongsTo
    {
        return $this->belongsTo(SgocUser::class, 'sgoc_residence_guard_user_id', 'id');
    }

    /**
     * Get the sales management user that owns the Residence.
     */
    public function salesManagementUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_management_user_id', 'id');
    }

    /**
     * Get the sales management user that owns the Residence.
     */
    public function rtmUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rtm_user_id', 'id');
    }

    /**
     * Get the property management company that owns the Residence.
     */
    public function insuranceCompany(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompany::class, 'insurance_company_id', 'id');
    }

    /**
     * Get the receptionist user in the Residence.
     */
    public function receptionist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receptionist_user_id', 'id');
    }

    /**
     * Get the accountant user in the Residence.
     */
    public function accountant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accountant_user_id', 'id');
    }

    /**
     * Get the subdistrict that owns the Residence.
     */
    public function subdistrict(): BelongsTo
    {
        return $this->belongsTo(ThailandSubDistrict::class);
    }

    /**
     * Get the units that owns the Residence.
     */
    public function units(): HasMany
    {
        return $this->HasMany(Unit::class);
    }

    /**
     * Get the unit users that owns the Residence.
     */
    public function unitUsers(): HasManyThrough
    {
        return $this->hasManyThrough(UnitUser::class, Unit::class);
    }

    /**
     * Get the announcements that owns the Residence.
     */
    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class);
    }

    /**
     * Get the visitors that owns the Residence.
     */
    public function visitors(): HasMany
    {
        return $this->hasMany(Visitor::class);
    }

    /**
     * Get the visitor setting that owns the Residence.
     */
    public function visitorSetting(): HasOne
    {
        return $this->hasOne(VisitorSetting::class)->with('media');
    }

    /**
     * Get the visitor cards that owns the Residence.
     */
    public function visitorCards(): HasMany
    {
        return $this->hasMany(VisitorCard::class);
    }

    /**
     * Get the visitor purposes that owns the Residence.
     */
    public function visitorPurposes(): HasMany
    {
        return $this->hasMany(VisitorPurpose::class);
    }

    /**
     * Get the visitor remarks that owns the Residence.
     */
    public function visitorRemarks(): HasMany
    {
        return $this->hasMany(VisitorRemark::class);
    }

    /**
     * Get the blacklisted visitors that owns the Residence.
     */
    public function blacklistedVisitors(): HasMany
    {
        return $this->hasMany(BlacklistedVisitor::class);
    }

    /**
     * Get the events that owns the Residence.
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * Get the companies that owns the Residence.
     */
    public function companies(): MorphToMany
    {
        return $this->morphToMany(Company::class, 'companiable');
    }

    /**
     * Get the subscription expires that owns the Residence.
     */
    public function subscriptionExpires(): HasMany
    {
        return $this->hasMany(SubscriptionExpire::class);
    }

    /**
     * Get the activation modules that owns the Residence.
     */
    public function activationModules(): HasMany
    {
        return $this->hasMany(ActivationModule::class);
    }

    /**
     * Get parking in the Residence.
     */
    public function calculations(): HasOne
    {
        return $this->hasOne(Parking::class);
    }

    /**
     * Get the amenities that owns the Residence.
     */
    public function amenities(): HasMany
    {
        return $this->hasMany(Amenity::class);
    }

    /**
     * Get the other amenity that owns the Residence.
     */
    public function otherAmenity(): HasOne
    {
        return $this->hasOne(OtherAmenity::class)->with('media');
    }

    /**
     * Get the residence sgoc company that owns the Residence.
     */
    public function sgocCompany(): BelongsTo
    {
        return $this->belongsTo(CdpCompany::class, 'sgoc_company_id', 'id');
    }

    /**
     * Get the pvr site that owns the Residence.
     */
    public function pvrSite(): HasOne
    {
        return $this->hasOne(PvrSite::class, 'mmb_residence_id', 'id');
    }

    /**
     * Get the checkpoints for the residence.
     */
    public function checkpoints(): HasMany
    {
        return $this->hasMany(Checkpoint::class, 'mmb_residence_id');
    }

    /**
     * Get the activationStatus that owns the Residence
     *
     * @return BelongsTo
     */
    public function activationStatus()
    {
        return $this->belongsTo(ResidenceActivationStatus::class, 'residence_activation_status_id');
    }

    /**
     * Get the residenceFeatures associated with the Residence
     */
    public function residenceFeatures(): HasMany
    {
        return $this->hasMany(ResidenceFeature::class);
    }

    /**
     * The locationTags that belong to the Residence
     *
     * @return BelongsToMany
     */
    public function locationTags(): HasMany
    {
        return $this->hasMany(ErpLocationTag::class);
    }

    /**
     * Many-to-many relation to FacilityAndAmenity via pivot.
     */
    public function facilityAndAmenities(): BelongsToMany
    {
        return $this->belongsToMany(FacilityAndAmenity::class, 'residence_amenity')
            ->using(ResidenceAmenity::class)
            ->withPivot(['id', 'is_active', 'is_claimable', 'is_bookable', 'deleted_at'])
            ->wherePivotNull('deleted_at')
            ->withTimestamps();
    }

    /**
     * One-to-many relation to pivot model for direct access.
     */
    public function residenceAmenities(): HasMany
    {
        return $this->hasMany(ResidenceAmenity::class);
    }

    /**
     * Get the guardPanelAccounts that owns by Residence.
     */
    public function guardPanelAccounts(): HasManyThrough
    {
        // User role for Guard Panel is Security Guard
        return $this->hasManyThrough(
            DtaDigitalTool::class,
            DigitalToolAllocation::class,
            'residence_id', // Foreign key on DigitalToolAllocation
            'digital_tool_allocation_id', // Foreign key on DtaDigitalTool
            'id', // Local key on Residence
            'id' // Local key on DigitalToolAllocation
        )
            ->whereHas('skuCenter.digitalToolRole', function (Builder $query) {
                $query->whereHas('digitalToolRoleCategory', function (Builder $q) {
                    $q->whereIn('name', ['Guard Panel', 'Guard Panel + Printer']);
                });
            });
    }

    /**
     * Get the guardTalkAccounts that owns by Residence.
     */
    public function guardTalkAccounts(): HasManyThrough
    {
        return $this->hasManyThrough(
            DtaDigitalTool::class,
            DigitalToolAllocation::class,
            'residence_id', // Foreign key on DigitalToolAllocation
            'digital_tool_allocation_id', // Foreign key on DtaDigitalTool
            'id', // Local key on Residence
            'id' // Local key on DigitalToolAllocation
        )
            ->whereHas('skuCenter.digitalToolRole', function (Builder $query) {
                $query->whereHas('digitalToolRoleCategory', function (Builder $q) {
                    $q->where('name', 'Guard Talk');
                });
            });
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
     */
    public function imageUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                $media = $this->getRelationValue('media');

                $url = $media && $media->isNotEmpty()
                    ? optional(
                        $media->firstWhere('collection_name', 'cover_image')
                    )->getFullUrl()
                    : 'https://dashboard.mymooban.co.th/images/no-image.png';

                return $url ?? 'https://dashboard.mymooban.co.th/images/no-image.png';
            }
        );
    }

    /**
     * Interact with the logo image url.
     */
    public function logoUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                $media = $this->getRelationValue('media');

                $url = $media && $media->isNotEmpty()
                    ? optional(
                        $media->firstWhere('collection_name', 'logo_image')
                    )->getFullUrl()
                    : 'https://dashboard.mymooban.co.th/images/no-image.png';

                return $url ?? 'https://dashboard.mymooban.co.th/images/no-image.png';
            }
        );
    }

    /**
     * Interact with the google map image url.
     */
    public function googleMapImageUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                $media = $this->getRelationValue('media');

                $url = $media && $media->isNotEmpty()
                    ? optional(
                        $media->firstWhere('collection_name', 'google_map_image')
                    )->getFullUrl()
                    : 'https://dashboard.mymooban.co.th/images/no-image.png';

                return $url ?? 'https://dashboard.mymooban.co.th/images/no-image.png';
            }
        );
    }

    /**
     * Interact with the guard house image url.
     */
    public function guardHouseImageUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                $media = $this->getRelationValue('media');

                $url = $media && $media->isNotEmpty()
                    ? optional(
                        $media->firstWhere('collection_name', 'guard_house_image')
                    )->getFullUrl()
                    : 'https://dashboard.mymooban.co.th/images/no-image.png';

                return $url ?? 'https://dashboard.mymooban.co.th/images/no-image.png';
            }
        );
    }

    public function warrantySetting(): HasOne
    {
        return $this->hasOne(WarrantySetting::class);
    }
}
