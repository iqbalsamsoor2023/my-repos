<?php

namespace App\Filament\Resources\FacilityAndAmenities\Pages;

use Filament\Actions\CreateAction;
use Filament\Schemas\Components\Tabs\Tab;
use App\Filament\Resources\FacilityAndAmenities\FacilityAndAmenityResource;
use App\Models\FacilityAndAmenity;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListFacilityAndAmenities extends ListRecords
{
    protected static string $resource = FacilityAndAmenityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $tabs['all'] = Tab::make();

        foreach (FacilityAndAmenity::get() as $facilityAndAmenity) {
            $tabs[$facilityAndAmenity->type] = Tab::make()->modifyQueryUsing(fn (Builder $query) => $query->where('type', $facilityAndAmenity->type));
        }

        return $tabs;
    }
}
