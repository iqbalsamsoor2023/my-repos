<?php

namespace App\Filament\Resources\Parcels\Widgets;

use App\Policies\ParcelPolicy;
use App\Services\ParcelWidgetDataService;
use App\Support\WidgetColorPalette;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class ParcelStatusStatsOverview extends BaseWidget
{
    use InteractsWithPageTable;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    protected function getColumns(): int
    {
        return 4;
    }

    public static function canView(): bool
    {
        return ParcelPolicy::hasDashboardAccess(Auth::user());
    }

    protected function getStats(): array
    {
        $data = ParcelWidgetDataService::getScopedDataForUser(Auth::user(), $this->tableFilters ?? []);
        $colorMap = WidgetColorPalette::parcelStatusColors();

        return [
            Stat::make(
                new HtmlString("<span style=\"color:{$colorMap['today']}; font-weight:bold;\">".__('parcel.parcels_today').'</span>'),
                $data['today_count']
            )->description(__('app.created_today'))->color('primary'),

            Stat::make(
                new HtmlString("<span style=\"color:{$colorMap['picked_up']}; font-weight:bold;\">".__('parcel.parcels_picked_up').'</span>'),
                $data['picked_up_count']
            )->description(__('parcel.picked_up'))->color('success'),

            Stat::make(
                new HtmlString("<span style=\"color:{$colorMap['pending']}; font-weight:bold;\">".__('parcel.parcels_pending').'</span>'),
                $data['pending_count']
            )->description(__('parcel.pending_pickup'))->color('warning'),

            Stat::make(
                new HtmlString("<span style=\"color:{$colorMap['not_my_parcel']}; font-weight:bold;\">".__('parcel.parcels_not_mine').'</span>'),
                $data['not_my_parcel_count']
            )->description(__('parcel.not_my_parcel'))->color('danger'),
        ];
    }
}
