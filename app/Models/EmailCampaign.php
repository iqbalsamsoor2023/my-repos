<?php

namespace App\Models;

use App\Enums\User\RoleType;
use Exception;
use App\Services\EmailCampaignService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class EmailCampaign extends Model implements HasMedia
{
    use InteractsWithMedia;

    //     Phase 1 > to all
    // Phase 2 > select by province
    // Select by District
    // Select by Sub-district
    // Select by Residence Type
    // Select by Residence
    protected $fillable = [
        'subject',
        'message',
        'send_to_all',
        'target_filters',
        'status',
        'total_recipients',
        'sent_count',
        'failed_count',
        'progress',
    ];

    protected $casts = [
        'send_to_all' => 'boolean',
        'target_filters' => 'array',
        'sent_count' => 'integer',
        'failed_count' => 'integer',
        'progress' => 'float',
    ];

    public function recipients()
    {
        return $this->hasMany(EmailCampaignRecipient::class);
    }

    /**
     * Register media collections for email campaign images.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('email_campaign_cover_images')
            ->acceptsMimeTypes(['image/jpeg', 'image/jpg', 'image/png', 'image/webp'])
            ->singleFile();
    }

    /**
     * Register media conversions for email-optimized images.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        // Email-optimized rectangle version (3:2 aspect ratio - 600x400px)
        // Perfect for email clients and maintains consistent rectangular shape
        $this->addMediaConversion('email-optimized')
            ->fit('crop', 600, 400)
            ->sharpen(10)
            ->optimize()
            ->quality(85)
            ->format('jpg')
            ->performOnCollections('email_campaign_cover_images');

        // Thumbnail for admin interface (same 3:2 aspect ratio)
        $this->addMediaConversion('thumb')
            ->fit('crop', 300, 200)
            ->sharpen(10)
            ->quality(90)
            ->format('jpg')
            ->performOnCollections('email_campaign_cover_images');

        // WebP version for modern browsers (same 3:2 aspect ratio)
        $this->addMediaConversion('webp')
            ->fit('crop', 600, 400)
            ->quality(80)
            ->format('webp')
            ->performOnCollections('email_campaign_cover_images');
    }

    /**
     * Update campaign status to sending when emails start being processed
     */
    public function markAsSending(): void
    {
        $this->update(['status' => 'sending']);
    }

    /**
     * Scope to get users matching the target filters
     * Includes Unit Owners and Unit Tenants
     */
    public function getTargetUsersQuery()
    {
        $filters = $this->target_filters ?? [];

        $query = User::query()
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->whereNull('deleted_at')
            ->whereHas('roles', function ($roleQuery) {
                $roleQuery->whereIn('name', [
                    RoleType::UNIT_OWNER->value,
                    RoleType::UNIT_TENANT->value,
                ]);
            })
            ->whereHas('unitUsers', function ($unitUserQuery) use ($filters) {
                $unitUserQuery->whereNull('deleted_at');

                // Apply geographic and residence filters only if send_to_all is false
                if (! $this->send_to_all) {
                    $unitUserQuery->whereHas('unit.residence', function ($residenceQuery) use ($filters) {
                        if (! empty($filters['residences'])) {
                            $residenceQuery->whereIn('id', $filters['residences']);
                        }

                        if (! empty($filters['mooban_types'])) {
                            $residenceQuery->whereIn('mooban_type', $filters['mooban_types']);
                        }

                        if (! empty($filters['sub_districts'])) {
                            $residenceQuery->whereHas('subDistrict', function ($subDistrictQuery) use ($filters) {
                                $subDistrictQuery->whereIn('id', $filters['sub_districts']);
                            });
                        }

                        if (! empty($filters['districts'])) {
                            $residenceQuery->whereHas('subDistrict.district', function ($districtQuery) use ($filters) {
                                $districtQuery->whereIn('id', $filters['districts']);
                            });
                        }

                        if (! empty($filters['provinces'])) {
                            $residenceQuery->whereHas('subDistrict.district.province', function ($provinceQuery) use ($filters) {
                                $provinceQuery->whereIn('id', $filters['provinces']);
                            });
                        }
                    });
                }
            });

        return $query;
    }

    /**
     * Get the email campaign cover image URL.
     * Returns email-optimized version for better email delivery.
     *
     * @return Attribute
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                $mediaItems = $this->getMedia('email_campaign_cover_images');

                if (count($mediaItems) > 0) {
                    // Use email-optimized conversion for better delivery
                    try {
                        return $mediaItems[0]->getUrl('email-optimized');
                    } catch (Exception $e) {
                        // Fallback to original if conversion fails
                        return $mediaItems[0]->getFullUrl();
                    }
                }

                return 'https://dashboard.mymooban.co.th/images/no-image.png';
            }
        );
    }

    /**
     * Get the thumbnail URL for admin interface.
     */
    public function getThumbnailUrl(): string
    {
        $mediaItems = $this->getMedia('email_campaign_cover_images');

        if (count($mediaItems) > 0) {
            try {
                return $mediaItems[0]->getUrl('thumb');
            } catch (Exception $e) {
                return $mediaItems[0]->getFullUrl();
            }
        }

        return 'https://dashboard.mymooban.co.th/images/no-image.png';
    }

    /**
     * Get fresh campaign statistics for dashboard display
     */
    public function getFreshStats(): array
    {
        $emailService = app(EmailCampaignService::class);
        return $emailService->refreshCampaignStats($this->id);
    }

    /**
     * Get real-time progress percentage
     */
    public function getRealTimeProgress(): float
    {
        $stats = $this->getFreshStats();

        return $stats['progress'] ?? 0;
    }

    /**
     * Check if campaign is currently active (sending emails)
     */
    public function isActive(): bool
    {
        $stats = $this->getFreshStats();

        return $stats['is_active'] ?? false;
    }
}
