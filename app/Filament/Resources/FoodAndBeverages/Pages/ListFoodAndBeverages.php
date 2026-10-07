<?php

namespace App\Filament\Resources\FoodAndBeverages\Pages;

use Filament\Actions\CreateAction;
use Filament\Tables\Filters\SelectFilter;
use App\Enums\LogisticPartner\CategoryType;
use App\Enums\LogisticPartner\ModesType;
use App\Filament\Resources\FoodAndBeverages\FoodAndBeveragesResource;
use App\Models\LogisticPartner;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListFoodAndBeverages extends ListRecords
{
    protected static string $resource = FoodAndBeveragesResource::class;

    protected function getTableQuery(): Builder
    {
        return LogisticPartner::query()->where('category', '=', CategoryType::FoodDelivery->value);
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('menu.new_food_delivery')),
        ];
    }

    public function getTableFilters(): array
    {
        return [
            SelectFilter::make('modes')
                ->label(__('Modes'))
                ->options(ModesType::options()),
        ];
    }
}
