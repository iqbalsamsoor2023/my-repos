<?php

namespace App\Providers;

use Filament\Facades\Filament;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Support\ServiceProvider;

class FilamentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register() {}

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        FilamentColor::register([
            'gray' => Color::Gray,
            'red' => Color::Red,
            'orange' => Color::Orange,
            'green' => Color::Green,
            'blue' => Color::Blue,
            'pink' => Color::Pink,
        ]);

        FilamentAsset::register([
            Css::make('report-download-widget', __DIR__.'/../../resources/css/report-download-widget.css'),

            Js::make('gt', asset('js/gtag.js')),
            Js::make('gtm', 'https://www.googletagmanager.com/gtag/js?id=UA-233768503-1'),
        ]);

        // Filament::registerScripts([
        //     'https://www.googletagmanager.com/gtag/js?id=UA-233768503-1',
        // ], true);

        // Filament::registerScripts([
        //     asset('js/gtag.js'),
        //     // asset('/sw.js'),
        //     // asset('js/init-service-worker.js'),
        // ]);

        // Filament::pushMeta([
        //     new HtmlString('<meta name="theme-color" content="#6777ef"/>'),
        //     new HtmlString('<link rel="apple-touch-icon" href="logo.png">'),
        //     new HtmlString('<link rel="manifest" href="/manifest.json">'),
        // ]);
    }
}
