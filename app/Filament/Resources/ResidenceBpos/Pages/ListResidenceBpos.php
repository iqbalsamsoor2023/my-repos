<?php

namespace App\Filament\Resources\ResidenceBpos\Pages;

use App\Filament\Resources\ResidenceBpos\ResidenceBpoResource;
use App\Filament\Resources\ResidenceBpos\Widgets\PropertyManagementTypeChart;
use App\Filament\Resources\ResidenceBpos\Widgets\PropertyManagementTypeStatsOverview;
use App\Filament\Resources\ResidenceBpos\Widgets\ResidenceDeveloperChart;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;

class ListResidenceBpos extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = ResidenceBpoResource::class;

    protected int|string|array $columnSpan = 'full';

    public function getHeaderWidgetsColumns(): int|array
    {
        return 3;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ResidenceDeveloperChart::class,
            PropertyManagementTypeChart::class,
            PropertyManagementTypeStatsOverview::class,
        ];
    }
}
