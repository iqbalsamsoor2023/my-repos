<?php

namespace App\Models;

use App\Enums\Unit\PropertyType;
use App\Events\UnitCreated;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ImportDataUnit extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $table = 'units';

    protected $fillable = [
        'id',
        'residence_id',
        'invitation_code_owner',
        'invitation_code_tenant',
        'home_id',
        'unit_size',
        'myseevr_link',
        'unit_number',
        'street',
        'floor',
        'block',
        'status',
        'move_in_at',
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
        'created' => UnitCreated::class,
    ];

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
     * Accessor to get unit's size in sqm or sqw.
     *
     * @return Attribute
     */
    public function getSpaceSizeAttribute(): string
    {
        $space_size = '';

        if ($this->residence->mooban_type == PropertyType::SINGLE_HOME || PropertyType::TOWN_HOME) {
            $space_size = isset($this->unit_size) ? ($this->unit_size * 4).' sqw' : '';
        } elseif ($this->residence->mooban_type == PropertyType::CONDO_HIGH_RISE || $this->residence->mooban_type == PropertyType::CONDO_LOW_RISE) {
            $space_size = isset($this->unit_size) ? $this->unit_size.' sqm' : '';
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
                $mediaItems = $this->getMedia('booking_form');
                $url = count($mediaItems) > 0 ? $mediaItems[0]->getFullUrl() : 'https://dashboard.mymooban.co.th/images/no-image.png';

                return $url;
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
                $mediaItems = $this->getMedia('house_contract');
                $url = count($mediaItems) > 0 ? $mediaItems[0]->getFullUrl() : 'https://dashboard.mymooban.co.th/images/no-image.png';

                return $url;
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
                $mediaItems = $this->getMedia('floor_plan');
                $url = count($mediaItems) > 0 ? $mediaItems[0]->getFullUrl() : 'https://dashboard.mymooban.co.th/images/no-image.png';

                return $url;
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
                $mediaItems = $this->getMedia('floor_plan_pdf');
                $url = count($mediaItems) > 0 ? $mediaItems[0]->getFullUrl() : 'https://dashboard.mymooban.co.th/images/no-image.png';

                return $url;
            }
        );
    }
}
