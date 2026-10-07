<?php

namespace App\Observers;

use Exception;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\MediaCollections\Filesystem;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaObserver
{
    public function deleted(Media $media)
    {
        if (in_array(SoftDeletes::class, class_uses_recursive($media))) {
            if (! $media->isForceDeleting()) {
                return;
            }
        }

        /** @var Filesystem $filesystem */
        $filesystem = app(Filesystem::class);

        $mediaDirectory = $filesystem->getMediaDirectory($media);
        $conversionsDirectory = $filesystem->getMediaDirectory($media, 'conversions');
        $responsiveImagesDirectory = $filesystem->getMediaDirectory($media, 'responsiveImages');

        collect([$mediaDirectory, $conversionsDirectory, $responsiveImagesDirectory])
            ->unique()
            ->each(function (string $directory) use ($media, $filesystem) {
                try {
                    $filesystem->removeFile($media, $directory.$media->file_name);
                } catch (Exception $exception) {
                    report($exception);
                }
            });
    }
}
