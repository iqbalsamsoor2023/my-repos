<?php

namespace App\Models\Erp;

use App\Models\MmbcnErpMedia;
use App\Models\Residence;
use App\Models\Sgoc\User as SgocUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class CdpCompany extends Model
{
    use SoftDeletes;
    
    protected $connection = 'mmbcnerp';

    protected $table = 'cdp_companies';

    public function __construct()
    {
        $this->table = DB::connection($this->connection)->getDatabaseName().'.'.$this->getTable();
    }

    protected $fillable = [
        'mmb_user_id',
        'business_category_id',
        'business_entity_id',
        'province_id',
        'billing_address',
        'delivery_address',
        'contact_number',
        'contact_email',
        'activation_status_id',
        'social_media',
        'custom_attributes',
    ];

    protected $casts = [
        'custom_attributes' => 'array',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'image_url',
    ];

    /**
     * Get the business entity of the CDP Company
     *
     * @return BelongsTo
     */
    public function businessEntity(): BelongsTo
    {
        return $this->belongsTo(BusinessEntity::class);
    }

    /**
     * Get the PMOC user of the CDP Company
     *
     * @return HasMany
     */
    public function pmocUser(): HasMany
    {
        return $this->setConnection('mysql')->hasMany(User::class, 'mmb_user_id');
    }

    /**
     * Get the user of the SGOC Company
     *
     * @return HasMany
     */
    public function users(): HasMany
    {
        return $this->setConnection('sgoc')->hasMany(SgocUser::class, 'company_id');
    }

    /**
     * Get the residences of the CDP Company
     *
     * @return HasMany
     */
    public function residences(): HasMany
    {
        return $this->hasMany(Residence::class, 'property_management_id');
    }

    public function contacts(): MorphMany
    {
        return $this->morphMany(Contact::class, 'contactable');
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                $media = MmbcnErpMedia::where('model_type', 'App\\Models\\BusinessEntity')->where('model_id', $this->business_entity_id)->where('collection_name', 'business_logo')->first();

                if ($media) {
                    $logoUrl = config('services.mmbcnerp.url').'/storage/business-entities/'.$this->business_entity_id.'/business-logo/'.$media->file_name;
                } else {
                    $logoUrl = 'https://dashboard.mymooban.co.th/images/no-image.png';
                }

                return $logoUrl;
            }
        );
    }
}
