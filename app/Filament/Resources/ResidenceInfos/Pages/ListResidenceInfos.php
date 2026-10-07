<?php

namespace App\Filament\Resources\ResidenceInfos\Pages;

use App\Filament\Resources\ResidenceInfos\ResidenceInfoResource;
use App\Filament\Resources\ResidenceInfos\Widgets\CctvPresenceChart;
use App\Filament\Resources\ResidenceInfos\Widgets\CctvRangeStatsOverview;
use App\Filament\Resources\ResidenceInfos\Widgets\EntranceBarrierTypeChart;
use App\Filament\Resources\ResidenceInfos\Widgets\EntranceBarrierTypeStatsOverview;
use App\Filament\Resources\ResidenceInfos\Widgets\EntryNumberChart;
use App\Filament\Resources\ResidenceInfos\Widgets\EntryNumberStatsOverview;
use App\Filament\Resources\ResidenceInfos\Widgets\GuardHouseLaneTypeChart;
use App\Filament\Resources\ResidenceInfos\Widgets\GuardHouseLaneTypeStatsOverview;
use App\Filament\Resources\ResidenceInfos\Widgets\GuardHouseRoofChart;
use App\Filament\Resources\ResidenceInfos\Widgets\GuardHouseRoofStatsOverview;
use App\Filament\Resources\ResidenceInfos\Widgets\InternetProviderChart;
use App\Filament\Resources\ResidenceInfos\Widgets\InternetProviderStatsOverview;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;

class ListResidenceInfos extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = ResidenceInfoResource::class;

    protected int|string|array $columnSpan = 'full';

    public function getHeaderWidgetsColumns(): int|array
    {
        return 3;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            EntryNumberChart::class,
            EntryNumberStatsOverview::class,
            GuardHouseLaneTypeChart::class,
            GuardHouseLaneTypeStatsOverview::class,
            GuardHouseRoofChart::class,
            GuardHouseRoofStatsOverview::class,
            EntranceBarrierTypeChart::class,
            EntranceBarrierTypeStatsOverview::class,
            InternetProviderChart::class,
            InternetProviderStatsOverview::class,
            CctvPresenceChart::class,
            CctvRangeStatsOverview::class,
        ];
    }


}
