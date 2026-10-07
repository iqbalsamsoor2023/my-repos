<?php

namespace App\Filament\Resources\ClaimableTitles\Schemas;

use App\Enums\FacilityAndAmenity\AmenityTypeEnum;
use App\Enums\FacilityAndAmenity\FacilityAmenityTypeEnum;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class ClaimableTitleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('menu.claimable_titles'))
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label(__('app.name'))
                            ->required()
                            ->unique(ignorable: fn(?Model $record): ?Model => $record)
                            ->maxLength(255),
                        TextInput::make('name_in_thai')
                            ->label(__('app.name_th'))
                            ->required()
                            ->unique(ignorable: fn(?Model $record): ?Model => $record),
                        Radio::make('category')
                            ->label(__('support-ticket.category'))
                            ->required()
                            ->reactive()
                            ->options(FacilityAmenityTypeEnum::options())
                            ->afterStateUpdated(function (callable $set, $state) {
                                if ($state === FacilityAmenityTypeEnum::FACILITY->value) {
                                    $set('type', null);
                                }
                            }),
                        Radio::make('type')
                            ->label(__('app.type'))
                            ->required()
                            ->reactive()
                            ->options(AmenityTypeEnum::options())
                            ->visible(fn(Get $get) => $get('category') == FacilityAmenityTypeEnum::AMENITY->value)
                            ->dehydrateStateUsing(function ($state, Get $get) {
                                return $get('category') === FacilityAmenityTypeEnum::AMENITY->value ? $state : null;
                            }),
                    ])
            ]);
    }
}
