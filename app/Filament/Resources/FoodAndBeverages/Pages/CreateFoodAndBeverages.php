<?php

namespace App\Filament\Resources\FoodAndBeverages\Pages;

use App\Enums\LogisticPartner\CategoryType;
use App\Filament\Resources\FoodAndBeverages\FoodAndBeveragesResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateFoodAndBeverages extends CreateRecord
{
    protected static string $resource = FoodAndBeveragesResource::class;

    public function getTitle(): string
    {
        return __('menu.create_food_delivery');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Model
    {
        $data['category'] = CategoryType::FoodDelivery->value;

        return static::getModel()::create($data);
    }
}
