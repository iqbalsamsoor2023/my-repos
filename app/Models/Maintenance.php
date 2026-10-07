<?php

namespace App\Models;

use App\Enums\Maintenance\MaintenanceStatus;
use App\Enums\Maintenance\MaintenanceVerificationStatus;
use App\Events\MaintenanceCreated;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use RyanChandler\Comments\Concerns\HasComments;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Maintenance extends BaseModel implements HasMedia
{
    use HasComments, HasFactory, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'maintainable_id',
        'maintainable_type',
        'maintainable_claim_number',
        'private_claim_category_id',
        'private_claim_item_id',
        'private_claim_item_title_id',
        'private_claim_snapshot',
        'miscellaneous',
        'claimable_item_details',
        'issue_description',
        'appointment_datetime',
        'status',
        'completion_datetime',
        'completed_remark',
        'is_verified',
        'rating',
        'verification_description',
        'reported_by',
        'warranty_period',
        'other_private_claim_item',
        // 'other_private_claim_category',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'claimable_item_details' => 'array',
        'private_claim_snapshot' => 'array',
    ];

    /**
     * The event map for the model.
     *
     * @var array
     */
    protected $dispatchesEvents = [
        'created' => MaintenanceCreated::class,
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'image_url',
        'image_urls',
        'image_verification_url',
        'image_completed_url',
        'private_claim_title',
        'public_claim_title',
    ];

    /**
     * Get the parent maintainable model (claimable item or residence amenity or residence amenity option or unit).
     */
    public function maintainable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the maintenance progressions for the Maintenance.
     */
    public function maintenanceProgressions(): HasMany
    {
        return $this->hasMany(MaintenanceProgression::class);
    }

    /**
     * Get the user that makes a Maintenance report.
     */
    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by', 'id');
    }

    /**
     * Get all of the post's comments.
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function privateClaimItem(): BelongsTo
    {
        return $this->belongsTo(PrivateClaimItem::class);
    }

    public function privateClaimItemTitle(): BelongsTo
    {
        return $this->belongsTo(PrivateClaimItemTitle::class);
    }

    public function privateClaimCategory(): BelongsTo
    {
        return $this->belongsTo(PrivateClaimCategory::class);
    }

    /**
     * Get the maintenance's status.
     */
    protected function statusLabel(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) => match ($attributes['status']) {
                MaintenanceStatus::PENDING->value => __('app.'.Str::lower(MaintenanceStatus::PENDING->name)),
                MaintenanceStatus::IN_PROGRESS->value => __('app.'.Str::lower(MaintenanceStatus::IN_PROGRESS->name)),
                MaintenanceStatus::COMPLETE->value => __('app.'.Str::lower(MaintenanceStatus::COMPLETE->name)),
                default => null,
            }
        );
    }

    /**
     * Get the maintenance's verification status.
     */
    protected function isVerified(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if ($value == '1') {
                    return Str::title(MaintenanceVerificationStatus::VERIFIED->name);
                } elseif ($value == '0') {
                    return str_replace('_', ' ', Str::title(MaintenanceVerificationStatus::NOT_VERIFIED->name));
                } else {
                    return null;
                }
            }
        );
    }

    /**
     * Interact with the maintenance's images url.
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                $mediaItems = $this->getMedia('maintenance_images');

                if (! empty($mediaItems)) {
                    $urls = [];
                    foreach ($mediaItems as $mediaItem) {
                        $urls[] = $mediaItem->getFullUrl();
                    }

                    $url = collect($urls)->implode(',');
                } else {
                    $url = 'https://dashboard.mymooban.co.th/images/no-image.png';
                }

                return $url;
            }
        );
    }

    /**
     * Interact with the maintenance's images url.
     */
    protected function imageUrls(): Attribute
    {
        return Attribute::make(
            get: function () {
                $mediaItems = $this->getMedia('maintenance_images');

                if (! empty($mediaItems)) {
                    $urls = [];
                    foreach ($mediaItems as $mediaItem) {
                        $urls[] = $mediaItem->getFullUrl();
                    }

                    $url = collect($urls);
                } else {
                    $url = ['https://dashboard.mymooban.co.th/images/no-image.png'];
                }

                return $url;
            }
        );
    }

    /**
     * Interact with the maintenance's verification image url.
     */
    protected function imageVerificationUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                $mediaItems = $this->getMedia('maintenance_verification_image');
                $url = count($mediaItems) > 0 ? $mediaItems[0]->getFullUrl() : 'https://dashboard.mymooban.co.th/images/no-image.png';

                return $url;
            }
        );
    }

    /**
     * Interact with the maintenance's verification image url.
     */
    protected function imageCompletedUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                $mediaItems = $this->getMedia('maintenance_completed_images');
                $url = count($mediaItems) > 0 ? $mediaItems[0]->getFullUrl() : 'https://dashboard.mymooban.co.th/images/no-image.png';

                return $url;
            }
        );
    }

    /**
     * Interact with the private maintenance's amenity.
     */
    protected function privateClaimTitle(): Attribute
    {
        return Attribute::make(
            get: function () {
                $language = app()->getLocale();

                if (empty($this->claimable_item_details) == false) {
                    $amenity_name = $this->claimable_item_details['amenity_name']
                        ?? (($language === 'th' && ! empty($this->claimable_item_details['claimable_title_name_in_thai']))
                            ? $this->claimable_item_details['claimable_title_name_in_thai']
                            : ($this->claimable_item_details['claimable_title_name'] ?? null));

                    if ($amenity_name == 'Others') {
                        $amenity_name = isset($this->miscellaneous) ? 'Others - '.$this->miscellaneous : $amenity_name;
                    }
                }

                return $amenity_name ?? null;
            }
        );
    }

    /**
     * Interact with the public maintenance's report title.
     */
    protected function publicClaimTitle(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->maintainable_type == 'App\\Models\\ClaimableItem') {
                    $title = $this->maintainable->item;

                    if ($title == 'Others') {
                        $title = isset($this->miscellaneous) ? 'Others - '.$this->miscellaneous : $title;
                    }
                }

                return $title ?? null;
            }
        );
    }
}
