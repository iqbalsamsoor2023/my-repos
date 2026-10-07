<?php

namespace App\Filament\Resources\Parcels\Widgets;

use App\Policies\ParcelPolicy;
use App\Services\ParcelWidgetDataService;
use App\Support\WidgetColorPalette;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\HtmlString;

class PmsStatsOverview extends BaseWidget
{
    protected ?string $pollingInterval = null;

    public static function canView(): bool
    {
        if (Route::currentRouteName() === 'filament.pages.dashboard') {
            return false;
        }

        return ParcelPolicy::isPropertyManager(Auth::user());
    }

    protected function getStats(): array
    {
        $data = ParcelWidgetDataService::getScopedDataForUser(Auth::user());
        $colorMap = WidgetColorPalette::parcelStatusColors();
        $total = $data['total_parcels'];

        return [
            Stat::make(
                new HtmlString("<span style=\"color:{$colorMap['today']}; font-weight:bold;\">".__('parcel.parcels_today').'</span>'),
                $data['today_count']
            )->description("from {$total} total Parcels")->color('primary'),

            Stat::make(
                new HtmlString("<span style=\"color:{$colorMap['picked_up']}; font-weight:bold;\">".__('parcel.parcels_picked_up').'</span>'),
                $data['picked_up_count']
            )->description("from {$total} total Parcels")->color('success'),

            Stat::make(
                new HtmlString("<span style=\"color:{$colorMap['pending']}; font-weight:bold;\">".__('parcel.parcels_pending').'</span>'),
                $data['pending_count']
            )->description("from {$total} total Parcels")->color('warning'),

            Stat::make(
                new HtmlString("<span style=\"color:{$colorMap['not_my_parcel']}; font-weight:bold;\">".__('parcel.parcels_not_mine').'</span>'),
                $data['not_my_parcel_count']
            )->description("from {$total} total Parcels")->color('danger'),
        ];
    }
}
