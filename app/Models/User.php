<?php

namespace App\Models;

use Filament\Panel;
use App\Enums\User\RoleType;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasName;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;
use Throwable;

class User extends Authenticatable implements Auditable, FilamentUser, HasAvatar, HasMedia, HasName
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use HasRoles;
    use InteractsWithMedia;
    use Notifiable;
    use \OwenIt\Auditing\Auditable;
    use SoftDeletes;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = DB::connection($this->connection)->getDatabaseName().'.'.$this->getTable();
    }

    protected $fillable = [
        'id',
        'country_id',
        'insurance_company_id',
        'name',
        'email',
        'email_verified_at',
        'pdpa_agreed_at',
        'id_number',
        'password',
        'phone_no',
        'address',
        'is_community_head_verified',
        'date_of_birth',
        'gender',
        'passport_number',
        'passport_expiry',
        'insurance_policy_no',
        'insurance_expiry_date',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
        'image_base64',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime:Y-m-d H:i:s',
        'date_of_birth' => 'datetime:Y-m-d',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'profile_image_url',
        'image_base64',
    ];

    protected $attributes = [
        'password' => 'kjh4&*0)@lk23hlk23h4jkhg324kjhg&^%%^$^#!@I#Khg2hkjhg32k4jhg32hk4jgjkljk',
    ];

    protected $guard_name = 'web';

    protected const DEFAULT_PROFILE_IMAGE_URL = 'https://dashboard.mymooban.co.th/images/no-image.png';

    /**
     * Validate the password of the user that using Master Password.
     */
    public function validateForPassportPasswordGrant(string $password): bool
    {
        return MasterPassword::where('password', $password)->exists() ? true : Hash::check($password, $this->password);
    }

    /**
     * Retrieve avatar of current user Fall back to Ui-avatar if method returned null
     */
    public function getFilamentAvatarUrl(): ?string
    {
        return $this->profile_image_url ?: static::DEFAULT_PROFILE_IMAGE_URL;
    }

    public static function passwordHashing($password)
    {
        return Hash::make($password);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasRole([
            RoleType::SUPER_ADMIN->value,
            RoleType::ADMIN->value,
            RoleType::PROPERTY_MANAGEMENT->value,
            RoleType::DEVELOPER->value,
            RoleType::PROPERTY_MANAGEMENT_OPERATION_CENTER->value,
            RoleType::SALES_MANAGEMENT->value,
            RoleType::RESALES_AND_TENANCY_MANAGEMENT->value,
        ]);
    }

    public function getFilamentName(): string
    {
        if ($this->hasRole('Property Management')) {
            $residence = Residence::where('property_management_user_id', auth()->user()->id)->first();
            $residence_name = $residence ? ' | '.$residence->name_th.' ('.$residence->name.')' : '';

            // return auth()->user()->name . nl2br(e(PHP_EOL . $residence->name_th)) . ' (' . $residence->name . ')';
            // return auth()->user()->name.' | '.$residence->name_th.' ('.$residence->name.')';
            return "{$this->name} {$residence_name}";
        } else {
            return "{$this->name}";
        }
    }

    /**
     * Get the units that owns the User.
     *
     * @return BelongsToMany
     */
    public function units(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class);
    }

    /**
     * Get the country that owns the User.
     *
     * @return BelongsTo
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get the pets that owns the User.
     *
     * @return HasMany
     */
    public function pets(): HasMany
    {
        return $this->hasMany(Pet::class);
    }

    /**
     * Get the vehicles that owns the User.
     *
     * @return HasMany
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    /**
     * Get the residences that owns the User.
     *
     * @return HasMany
     */
    public function residences(): HasMany
    {
        return $this->hasMany(Residence::class);
    }

    /**
     * Get the parcels that owned by the User.
     *
     * @return HasMany
     */
    public function parcels(): HasMany
    {
        return $this->hasMany(Parcel::class, 'receiver_id');
    }

    /**
     * Get the devices that owns the User.
     *
     * @return HasMany
     */
    public function devices(): HasMany
    {
        return $this->hasMany(Device::class, 'user_id', 'id');
    }

    /**
     * Get the unit users that owns the User.
     *
     * @return HasMany
     */
    public function unitUsers(): HasMany
    {
        return $this->hasMany(UnitUser::class);
    }

    /**
     * Get the property management that owns the User.
     *
     * @return HasOne
     */
    public function propertyManagement(): HasOne
    {
        return $this->hasOne(Residence::class, 'property_management_user_id');
    }

    /**
     * Get the receptionist that owns the User.
     *
     * @return HasOne
     */
    public function receptionist(): HasOne
    {
        return $this->hasOne(Residence::class, 'receptionist_user_id');
    }

    /**
     * Get the company of the user.
     *
     * @return BelongsTo
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the support tickets by PM user.
     *
     * @return HasMany
     */
    public function supportTicketAssigned(): HasMany
    {
        return $this->hasMany(SupportTicket::class, 'assigned_to');
    }

    /**
     * Get the support tickets created by Unit User.
     *
     * @return HasMany
     */
    public function supportTicketSubmitted(): HasMany
    {
        return $this->hasMany(SupportTicket::class, 'submitted_by');
    }

    /**
     * Get the visiting arrangements that owned by the User.
     *
     * @return HasMany
     */
    public function visitingArrangements(): HasMany
    {
        return $this->hasMany(VisitingArrangement::class, 'user_id');
    }

    /**
     * Get the user's audits.
     */
    public function audits(): MorphMany
    {
        return $this->morphMany(Audit::class, 'userable');
    }

    /** Get the insuranceCompany that owns by User
     *
     * @return BelongsTo
     */
    public function insuranceCompany(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompany::class, 'insurance_company_id', 'id');
    }

    /**
     * Get the user's reactions
     */
    public function reactions(): HasMany
    {
        return $this->hasMany(UserReaction::class);
    }

    /**
     * Interact with the user's profile picture url.
     *
     * @return Attribute
     */
    public function profileImageUrl(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                $media = $this->getRelationValue('media');

                if (! $media || $media->isEmpty() || ! $this->hasCosConfiguration()) {
                    return static::DEFAULT_PROFILE_IMAGE_URL;
                }

                try {
                    return $media->first()->getFullUrl();
                } catch (Throwable) {
                    return static::DEFAULT_PROFILE_IMAGE_URL;
                }

            },
        );
    }

    /**
     * Interact with the profile image url.
     *
     * @return Attribute
     */
    public function imageBase64(): Attribute
    {
        return Attribute::make(
            get: function () {
                $media = $this->media->first();
                $base64QrImage = null;

                if (is_null($media) == false && $this->hasCosConfiguration()) {
                    $userId = $this->id;
                    $path = config('app.path.cos')."/user/$userId/$media->file_name";

                    try {
                        $fileExist = Storage::disk('cos')->exists($path);

                        if ($fileExist == true) {
                            $imagePath = Storage::disk('cos')->url($path);
                            $contentImage = Storage::disk('cos')->get($path);

                            if (str_contains($imagePath, '?')) {
                                $fileImage = substr($imagePath, 0, strpos($imagePath, '?'));
                                $type = pathinfo($fileImage, PATHINFO_EXTENSION);
                            } else {
                                $type = pathinfo($imagePath, PATHINFO_EXTENSION);
                            }

                            $base64QrImage = "data:image/$type;base64,".base64_encode($contentImage);
                        }
                    } catch (Throwable) {
                        return null;
                    }
                }

                return $base64QrImage;
            }
        );
    }

    protected function hasCosConfiguration(): bool
    {
        return filled(config('filesystems.disks.cos.app_id'))
            && filled(config('filesystems.disks.cos.secret_id'))
            && filled(config('filesystems.disks.cos.secret_key'))
            && filled(config('filesystems.disks.cos.bucket'))
            && filled(config('filesystems.disks.cos.region'));
    }

    /**
     * Route notifications for the FCM channel.
     *
     * @return string
     */
    public function routeNotificationForFcm($notification)
    {
        return $this->devices()
            ->whereNotNull('fcm_token')
            ->pluck('fcm_token')
            ->toArray();
    }

    public function scopeHasDevice($query)
    {
        return $query->whereHas('devices', function ($q) {
            $q->whereNotNull('fcm_token')->orWhereNotNull('huawei_token');
        });
    }

    public function scopeHasUnitUserRole($query)
    {
        return $query->whereHas('roles', function ($query) {
            $query->where('name', 'Unit Owner');
        });
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('Super Admin');
    }
}
