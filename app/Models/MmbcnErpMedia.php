<?php

namespace App\Models;

use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MmbcnErpMedia extends Media
{
    protected $connection = 'mmbcnerp';
}
