<?php

namespace App\Filament\Resources\Vehicles\Pages;

use App\Filament\Resources\Vehicles\VehicleResource;
use App\Filament\Resources\Vehicles\Widgets\Car\CarAgeChart;
use App\Filament\Resources\Vehicles\Widgets\Car\CarAgeStatsOverview;
use App\Filament\Resources\Vehicles\Widgets\Car\CarBodyTypeChart;
use App\Filament\Resources\Vehicles\Widgets\Car\CarBodyTypeStatsOverview;
use App\Filament\Resources\Vehicles\Widgets\Car\CarBrandsChart;
use App\Filament\Resources\Vehicles\Widgets\Car\CarBrandsStatsOverview;
use App\Filament\Resources\Vehicles\Widgets\Car\CarFuelTypeChart;
use App\Filament\Resources\Vehicles\Widgets\Car\CarFuelTypeStatsOverview;
use App\Filament\Resources\Vehicles\Widgets\Car\CarInsuranceCompanyStatsOverview;
use App\Filament\Resources\Vehicles\Widgets\Car\CarTopInsuranceChart;
use App\Filament\Resources\Vehicles\Widgets\Motorcycle\MotorcycleAgeChart;
use App\Filament\Resources\Vehicles\Widgets\Motorcycle\MotorcycleAgeStatsOverview;
use App\Filament\Resources\Vehicles\Widgets\Motorcycle\MotorcycleBodyTypeChart;
use App\Filament\Resources\Vehicles\Widgets\Motorcycle\MotorcycleBodyTypeStatsOverview;
use App\Filament\Resources\Vehicles\Widgets\Motorcycle\MotorcycleBrandChart;
use App\Filament\Resources\Vehicles\Widgets\Motorcycle\MotorcycleBrandsStatsOverview;
use App\Filament\Resources\Vehicles\Widgets\Motorcycle\MotorcycleInsuranceCompanyStatsOverview;
use App\Filament\Resources\Vehicles\Widgets\Motorcycle\MotorcycleTopInsuranceChart;
use App\Filament\Resources\Vehicles\Widgets\SectionHeadingCar;
use App\Filament\Resources\Vehicles\Widgets\SectionHeadingMotorcycle;
use App\Filament\Resources\Vehicles\Widgets\VehicleCreationsChart;
use App\Filament\Resources\Vehicles\Widgets\VehicleTypeChart;
use Filament\Actions\CreateAction;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;

class ListVehicles extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = VehicleResource::class;

    protected int|string|array $columnSpan = 'full';

    public function getHeaderWidgetsColumns(): int|array
    {
        return 3;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            VehicleTypeChart::class,

            SectionHeadingCar::class,
            CarBrandsChart::class,
            CarBrandsStatsOverview::class,
            CarFuelTypeChart::class,
            CarFuelTypeStatsOverview::class,
            CarBodyTypeChart::class,
            CarBodyTypeStatsOverview::class,
            CarAgeChart::class,
            CarAgeStatsOverview::class,
            CarTopInsuranceChart::class,
            CarInsuranceCompanyStatsOverview::class,

            SectionHeadingMotorcycle::class,
            MotorcycleBrandChart::class,
            MotorcycleBrandsStatsOverview::class,
            MotorcycleBodyTypeChart::class,
            MotorcycleBodyTypeStatsOverview::class,
            MotorcycleAgeChart::class,
            MotorcycleAgeStatsOverview::class,
            MotorcycleTopInsuranceChart::class,
            MotorcycleInsuranceCompanyStatsOverview::class,

            VehicleCreationsChart::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('menu.new_vehicle')),
        ];
    }
}
