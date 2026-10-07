<?php

namespace App\Observers;

use App\Models\ActivationModule;
use Illuminate\Support\Facades\Cache;


class ActivationModuleObserver
{
    public function saved(ActivationModule $brand)
    {
        Cache::tags(['activation_modules'])->flush();
    }

    public function deleted(ActivationModule $brand)
    {
        Cache::tags(['activation_modules'])->flush();
    }
}
