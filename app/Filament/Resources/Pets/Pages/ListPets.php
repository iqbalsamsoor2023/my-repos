<?php

namespace App\Filament\Resources\Pets\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\Pets\PetResource;
use App\Filament\Resources\Pets\Widgets\PetsAgeChart;
use App\Filament\Resources\Pets\Widgets\PetsCreationChart;
use App\Filament\Resources\Pets\Widgets\PetsTypeChart;
use App\Filament\Resources\Pets\Widgets\PetsTypeStatsOverview;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;

class ListPets extends ListRecords
{
    protected static string $resource = PetResource::class;

    use ExposesTableToWidgets;

    protected int | string | array $columnSpan = 'full';

    public function getHeaderWidgetsColumns(): int|array
    {
        return 3;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            PetsTypeChart::class,
            PetsTypeStatsOverview::class,
            PetsAgeChart::class,
            PetsCreationChart::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('menu.new_pet')),
        ];
    }
}
