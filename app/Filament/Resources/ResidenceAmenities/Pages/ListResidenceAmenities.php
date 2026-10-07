<?php

namespace App\Filament\Resources\ResidenceAmenities\Pages;

use Filament\Actions\CreateAction;
use Filament\Schemas\Components\Tabs\Tab;
use App\Filament\Resources\ResidenceAmenities\ResidenceAmenityResource;
use App\Models\ResidenceAmenity;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\App;

class ListResidenceAmenities extends ListRecords
{
    protected static string $resource = ResidenceAmenityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('menu.create_residence_amenities_setting')),
        ];
    }

    public function getTabs(): array
    {
        $tabs = [];

        $isThai = App::getLocale() === 'th';

        $translatedLabels = [
            'all' => 'ทั้งหมด',
            'Amenity' => 'ตั้งค่าพื้นที่ให้จองใช้งาน+แจ้งซ่อม',
            'Facility' => 'ตั้งค่าพื้นที่แจ้งซ่อมเท่านั้น',
        ];

        $tabs[$isThai ? $translatedLabels['all'] : 'all'] = Tab::make();

        $types = ResidenceAmenity::with('facilityAndAmenity')
            ->get()
            ->pluck('facilityAndAmenity.type')
            ->unique()
            ->filter();

        foreach ($types as $type) {
            $label = $isThai ? ($translatedLabels[$type] ?? $type) : $type;
        
            $tabs[$label] = Tab::make()->modifyQueryUsing(function (Builder $query) use ($type) {
                $query->whereHas('facilityAndAmenity', function ($q) use ($type) {
                    $q->where('type', $type);
                });
            });
        }

        return $tabs;
    }
}
