<?php

namespace App\Filament\Resources\Couriers\Pages;

use Filament\Actions\CreateAction;
use Filament\Tables\Filters\SelectFilter;
use App\Enums\LogisticPartner\CategoryType;
use App\Filament\Resources\Couriers\CourierResource;
use App\Models\LogisticPartner;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class ListCouriers extends ListRecords
{
    protected static string $resource = CourierResource::class;

    protected function getTableQuery(): Builder
    {
        return LogisticPartner::query()->where('category', '=', CategoryType::Courier->value);
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('menu.new_courier')),
        ];
    }

    public function getTableFilters(): array
    {
        return [
            SelectFilter::make('modes')
                ->label(__('Modes'))
                ->options([
                    'Local' => __('Local'),
                    'International' => __('International'),
                ]),
        ];
    }
}
