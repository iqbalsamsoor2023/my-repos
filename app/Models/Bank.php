<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Bank extends Model implements HasMedia
{
    use InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'name',
        'name_th',
    ];

    protected function nameInEnglish(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->attributes['name'].' ('.$this->attributes['name_th'].')',
        );
    }

    protected function nameInThai(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->attributes['name_th'].' ('.$this->attributes['name'].')',
        );
    }
}
