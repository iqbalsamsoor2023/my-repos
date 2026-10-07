<?php

namespace App\Models;

use App\Enums\Checkpoint\CheckpointLogStatus;
use App\Models\Sgoc\Checkpoint;
use App\Models\Sgoc\CheckpointRound;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use App\Models\Sgoc\User as MysgocUser;

class CheckpointLog extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $connection = 'sgoc';

    protected $casts = [
        'questionnaires' => AsCollection::class,
        'status' => CheckpointLogStatus::class,
    ];

    /**
     * Get the checkpoint that owns the CheckpointLog.
     *
     * @return BelongsTo
     */
    public function checkpoint(): BelongsTo
    {
        return $this->belongsTo(Checkpoint::class);
    }

    /**
     * Get the checkpointRound that owns the Checkpoint Log.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function checkpointRound(): BelongsTo
    {
        return $this->belongsTo(CheckpointRound::class);
    }

    /**
     * Get the user that owns the CheckpointLog.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(MysgocUser::class);
    }

    /**
     * Interact with the checkpoint log's images url.
     *
     * @return Attribute
     */
    protected function imageUrls(): Attribute
    {
        return Attribute::make(
            get: function () {
                $mediaItems = $this->getMedia();

                if (! empty($mediaItems)) {
                    if (count($mediaItems) > 0) {
                        foreach ($mediaItems as $mediaItem) {
                            $urls[] = $mediaItem->getFullUrl();
                        }

                        $url = collect($urls)->implode(',');
                    } else {
                        $url = count($mediaItems) > 0 ? $mediaItems[0]->getFullUrl() : 'https://dashboard.mymooban.co.th/images/no-image.png';
                    }
                } else {
                    $url = count($mediaItems) > 0 ? $mediaItems[0]->getFullUrl() : 'https://dashboard.mymooban.co.th/images/no-image.png';
                }

                return $url;
            }
        );
    }
}
