<?php

namespace App\Filament\Resources\Couriers;

use App\Filament\Resources\Couriers\Pages\CreateCourier;
use App\Filament\Resources\Couriers\Pages\EditCourier;
use App\Filament\Resources\Couriers\Pages\ListCouriers;
use App\Filament\Resources\LogisticPartners\LogisticPartnerResource;
use App\Models\LogisticPartner;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;

class CourierResource extends Resource
{
    protected static ?string $model = LogisticPartner::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-ticket';

    protected static ?int $navigationSort = 8;

    protected static ?string $slug = 'couriers';

    public static function getNavigationGroup(): string
    {
        return __('menu.setting_management');
    }

    public static function getModelLabel(): string
    {
        return __('parcel.courier');
    }

    public static function getPluralModelLabel(): string
    {
        return __('menu.couriers');
    }

    public static function form(Schema $schema): Schema
    {
        return LogisticPartnerResource::form($schema);
    }

    public static function table(Table $table): Table
    {
        return LogisticPartnerResource::table($table)
            ->filtersLayout(FiltersLayout::AboveContentCollapsible);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCouriers::route('/'),
            'create' => CreateCourier::route('/create'),
            'edit' => EditCourier::route('/{record}/edit'),
        ];
    }
}
