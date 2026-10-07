<?php

namespace App\Models;

use App\Enums\HouseholdItem\ItemCategoryEnum;
use App\Enums\Residence\SubType;
use App\Enums\Unit\HouseType;
use App\Enums\Unit\PaymentFrequency;
use App\Enums\Unit\UnitSizeType;
use App\Events\UnitCreated;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Unit extends BaseModel implements HasMedia
{
    /**
     * @property HouseType|null $house_type
     */
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $table = 'units';

    protected $fillable = [
        'id',
        'residence_id',
        'invitation_code_owner',
        'invitation_code_tenant',
        'home_id',
        'unit_size',
        'land_size',
        'charge_type',
        'maintenance_cycle',
        'myseevr_link',
        'empty_room_vr_link',
        'sample_room_vr_link',
        'property_type',
        'myseevr_link',
        'house_type',
        'sub_type',
        'unit_number',
        'street',
        'floor',
        'block',
        'condo_room_type',
        'build_up_area_sqm',
        'storey',
        'status',
        'construction_progress',
        'move_in_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $appends = [
        'booking_form_pdf_url',
        'house_contract_url',
        'floor_plan_pdf_url',
        'floor_plan_url',
        'invitation_code_type',
    ];

    protected $casts = [
        'house_type' => HouseType::class,
        'unit_size' => 'decimal:2',
        'land_size' => 'decimal:2',
        'charge_type' => UnitSizeType::class,
        'maintenance_cycle' => PaymentFrequency::class,
    ];

    /**
     * The event map for the model.
     *
     * @var array
     */
    protected $dispatchesEvents = [
        'created' => UnitCreated::class,
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->table = DB::connection($this->connection)->getDatabaseName().'.'.$this->getTable();
    }

    protected static function booted()
    {
        static::deleting(function (Unit $unit) {
            $unit->unitUsers()->delete();
        });
    }

    /**
     * Get the residence that owns the Facility.
     *
     * @return BelongsTo
     */
    public function moobaan(): BelongsTo
    {
        return $this->belongsTo(Residence::class, 'residence_id', 'id');
    }

    /**
     * Get the residence that owns the Unit.
     *
     * @return BelongsTo
     */
    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    /**
     * Get the users that owns the Unit.
     *
     * @return BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * Get the owners that owns the Unit.
     *
     * @return HasMany
     */
    public function owners(): HasMany
    {
        return $this->hasMany(UnitUser::class)->where('is_owner', true)->whereHas('user', function ($q) {
            return $q->role('Unit Owner');
        });
    }

    /**
     * Get the tenants that owns the Unit.
     *
     * @return HasMany
     */
    public function tenants(): HasMany
    {
        return $this->hasMany(UnitUser::class)->where('is_owner', false)->whereHas('user', function ($q) {
            return $q->role('Unit Tenant');
        });
    }

    /**
     * Get the maintenances that owns the Unit.
     *
     * @return MorphMany
     */
    public function maintenances(): MorphMany
    {
        return $this->morphMany(Maintenance::class, 'maintainable');
    }

    /**
     * Get the parcels that owns the Unit.
     *
     * @return HasMany
     */
    public function parcels(): HasMany
    {
        return $this->hasMany(Parcel::class);
    }

    /**
     * Get the visitors that owns the Unit.
     *
     * @return HasMany
     */
    public function visitors(): HasMany
    {
        return $this->hasMany(Visitor::class);
    }

    /**
     * Get the pets that owns the Unit.
     *
     * @return HasMany
     */
    public function pets(): HasMany
    {
        return $this->hasMany(Pet::class);
    }

    /**
     * Get the vehicles that owns the Unit.
     *
     * @return HasMany
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    /**
     * Get the users that owns the Unit.
     *
     * @return HasMany
     */
    public function unitUsers(): HasMany
    {
        return $this->hasMany(UnitUser::class);
    }

    /**
     * Get the invoices that owns the Unit.
     *
     * @return HasMany
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get the furnitures that owns the Unit.
     *
     * @return HasMany
     */
    public function furnitures(): HasMany
    {
        return $this->hasMany(UnitHouseholdItem::class)
            ->whereHas('householdItem', function ($query) {
                $query->where('category', ItemCategoryEnum::FURNITURE->value);
            });
    }

    /**
     * Get the home appliances that owns the Unit.
     *
     * @return HasMany
     */
    public function homeAppliances(): HasMany
    {
        return $this->hasMany(UnitHouseholdItem::class)
            ->whereHas('householdItem', function ($query) {
                $query->where('category', ItemCategoryEnum::HOME_APPLIANCE->value);
            });
    }

    /**
     * Get the home appliances that owns the Unit.
     *
     * @return HasMany
     */
    public function livingSpaces(): HasMany
    {
        return $this->hasMany(UnitHouseholdItem::class)
            ->whereHas('householdItem', function ($query) {
                $query->where('category', ItemCategoryEnum::SPACE->value);
            });
    }

    /**
     * Get the home appliances that owns the Unit.
     *
     * @return HasMany
     */
    public function householdItems()
    {
        return $this->belongsToMany(HouseholdItem::class, 'unit_household_items')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    /**
     * Get the invoices that owns the Unit.
     *
     * @return HasMany
     */
    public function supportTickets(): HasMany
    {
        return $this->setConnection('mmbcnerp')->hasMany(SupportTicket::class);
    }

    /**
     * Get the Incident Reports that filed for this unit.
     *
     * @return HasMany
     */
    public function incidentReports(): HasMany
    {
        return $this->setConnection('sgoc')->hasMany(IncidentReport::class);
    }

    /**
     * The announcements targeted to this unit.
     *
     * @return BelongsToMany
     */
    public function announcements()
    {
        return $this->belongsToMany(Announcement::class, 'announcement_unit', 'unit_id', 'announcement_id');
    }

    public function rentAdvertisement(): HasOne
    {
        return $this->hasOne(RentAdvertisement::class)->latest();
    }

    public function resaleAdvertisement(): HasOne
    {
        return $this->hasOne(ResaleAdvertisement::class)->latest();
    }

    /**
     * Get the Sale Advertisement for this unit.
     *
     * @return HasOne
     */
    public function saleAdvertisement(): HasOne
    {
        return $this->hasOne(SaleAdvertisement::class)->latest();
    }

    /**
     * Accessor to get unit's size in sqm or sqw.
     *
     * @return Attribute
     */
    public function getSpaceSizeAttribute(): string
    {
        $space_size = '';

        if ($this->property_type == SubType::SINGLE_HOME->value || $this->property_type == SubType::TOWN_HOME->value) {
            $space_size = isset($this->unit_size) ? $this->unit_size.' sqw' : '';
        } elseif ((int) $this->property_type == (int) SubType::CONDO_HIGH_RISE->value || (int) $this->property_type == (int) SubType::CONDO_LOW_RISE->value) {
            $space_size = isset($this->unit_size) ? $this->unit_size.' sqm' : '';
        } else {
            $space_size = (int) $this->unit_size.' sqw';
        }

        return $space_size;
    }

    /**
     * Accessor to get all residents in the Unit.
     *
     * @return Attribute
     */
    protected function getResidentAttribute(): string
    {
        $unit_users = UnitUser::where('unit_id', $this->id)->get();
        $resident = '';

        if (count($unit_users) > 0) {
            foreach ($unit_users as $unit_user) {
                $user = User::findOrFail($unit_user->user_id);

                if (! $user) {
                    $resident = '';
                }

                $resident .= $user->name.', ';
            }
        }

        return $resident;
    }

    /**
     * Interact with the unit's booking form url.
     *
     * @return Attribute
     */
    protected function bookingFormPdfUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                $media = $this->getRelationValue('media');
    
                if (! $media) {
                    return 'https://dashboard.mymooban.co.th/images/no-image.png';
                }
    
                $item = $media->firstWhere('collection_name', 'booking_form');
    
                return $item
                    ? $item->getFullUrl()
                    : 'https://dashboard.mymooban.co.th/images/no-image.png';
            }
        );
    }

    /**
     * Interact with the unit's booking form url.
     *
     * @return Attribute
     */
    protected function houseContractUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                $media = $this->getRelationValue('media');
    
                if (! $media) {
                    return 'https://dashboard.mymooban.co.th/images/no-image.png';
                }
    
                $item = $media->firstWhere('collection_name', 'house_contract');
    
                return $item
                    ? $item->getFullUrl()
                    : 'https://dashboard.mymooban.co.th/images/no-image.png';
            }
        );
    }

    /**
     * Interact with the unit's floor plan url.
     *
     * @return Attribute
     */
    protected function floorPlanUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                $media = $this->getRelationValue('media');
    
                if (! $media) {
                    return 'https://dashboard.mymooban.co.th/images/no-image.png';
                }
    
                $item = $media->firstWhere('collection_name', 'floor_plan');
    
                return $item
                    ? $item->getFullUrl()
                    : 'https://dashboard.mymooban.co.th/images/no-image.png';
            }
        );
    }

    /**
     * Interact with the unit's floor plan pdf url.
     *
     * @return Attribute
     */
    protected function floorPlanPdfUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                $media = $this->getRelationValue('media');
    
                if (! $media) {
                    return 'https://dashboard.mymooban.co.th/images/no-image.png';
                }
    
                $item = $media->firstWhere('collection_name', 'floor_plan_pdf');
    
                return $item
                    ? $item->getFullUrl()
                    : 'https://dashboard.mymooban.co.th/images/no-image.png';
            }
        );
    }

    /**
     * Interact with the unit's invitation code type.
     *
     * @return Attribute
     */
    protected function invitationCodeType(): ?Attribute
    {
        $request = request()->input('invitation_code');

        return Attribute::make(
            get: function () use ($request) {
                if (isset($request)) {
                    $type = substr($request, 0, 1);

                    if ($type == 1) {
                        $typeId = 1;
                        $typeName = 'Owner';
                    } elseif ($type == 2) {
                        $typeId = 2;
                        $typeName = 'Tenant';
                    }

                    return [
                        'type_id' => $typeId,
                        'type' => $typeName,
                    ];
                }
            }
        );
    }
}
