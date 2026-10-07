<?php

namespace App\Models;

use Spatie\MediaLibrary\MediaCollections\Models\Media;

class SgocMedia extends Media
{
    protected $connection = 'sgoc';
}
