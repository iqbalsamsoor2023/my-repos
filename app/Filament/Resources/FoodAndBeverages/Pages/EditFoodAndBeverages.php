<?php

namespace App\Filament\Resources\FoodAndBeverages\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\FoodAndBeverages\FoodAndBeveragesResource;
use Filament\Resources\Pages\EditRecord;

class EditFoodAndBeverages extends EditRecord
{
    protected static string $resource = FoodAndBeveragesResource::class;

    public function getTitle(): string
    {
        return __('menu.edit_food_delivery');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
