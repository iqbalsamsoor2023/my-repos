<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class BillPayeeSetting extends BaseModel implements HasMedia
{
    use InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'id',
        'residence_id',
        'payee_name',
        'payee_name_th',
        'payee_email',
        'payee_phone_no',
        'payee_address',
        'payee_address_th',
        'remark',
        'remark_th',
        'created_at',
        'updated_at',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'image_base64',
        'image_url'
    ];

    /**
     * Get the residence that owns the BillPayeeSetting.
     *
     * @return BelongsTo
     */
    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    /**
     * Get the accounts that owns the BillPayeeSetting.
     *
     * @return HasMany
     */
    public function accounts(): HasMany
    {
        return $this->hasMany(BillPayeeBankDetail::class);
    }

    /**
     * Interact with the cover image url.
     *
     * @param  string  $value
     * @return Attribute
     */
    public function imageBase64(): Attribute
    {
        return Attribute::make(
            get: function () {
                $media = $this->media->first();
                $base64QrImage = null;

                if (is_null($media) == false) {
                    $billPayeeSettingId = $this->id;
                    $path = config('app.path.cos')."/bill-reminder/qr/$billPayeeSettingId/$media->file_name";
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
                }

                return $base64QrImage;
            }
        );
    }

    public function imageUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                $media = $this->media->first();

                if (is_null($media)) {
                    return null;
                }

                $billPayeeSettingId = $this->id;

                $path = config('app.path.cos') ."/bill-reminder/qr/$billPayeeSettingId/$media->file_name";

                if (!Storage::disk('cos')->exists($path)) {
                    return null;
                }

                return Storage::disk('cos')->url($path);
            }
        );
    }
}
