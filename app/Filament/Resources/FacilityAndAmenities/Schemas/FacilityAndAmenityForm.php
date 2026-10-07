<?php

namespace App\Filament\Resources\FacilityAndAmenities\Schemas;

use App\Enums\FacilityAndAmenity\AmenityTypeEnum;
use App\Enums\FacilityAndAmenity\FacilityAmenityTypeEnum;
use App\Models\ClaimableTitle;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;

class FacilityAndAmenityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('app.facility_amenity'))
                    ->description(__('app.facility_amenity_detail'))
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Fieldset::make(__('app.details'))
                            ->columnSpanFull()
                            ->schema([
                                TextInput::make('name')
                                    ->label(__('app.name'))
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignorable: fn (?Model $record): ?Model => $record),
                                TextInput::make('name_in_thai')
                                    ->label(__('app.name_th'))
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignorable: fn (?Model $record): ?Model => $record),
                                Radio::make('type')
                                    ->label(__('app.type'))
                                    ->required()
                                    ->reactive()
                                    ->options(FacilityAmenityTypeEnum::options())
                                    ->afterStateUpdated(function (callable $set, $state) {
                                        if ($state === FacilityAmenityTypeEnum::FACILITY->value) {
                                            $set('amenity_type', null);
                                        }
                                    }),
                                Radio::make('amenity_type')
                                    ->label(__('app.amenity_type'))
                                    ->required()
                                    ->reactive()
                                    ->options(AmenityTypeEnum::options())
                                    ->visible(fn (Get $get) => $get('type') == FacilityAmenityTypeEnum::AMENITY->value)
                                    ->dehydrateStateUsing(function ($state, Get $get) {
                                        return $get('type') === FacilityAmenityTypeEnum::AMENITY->value ? $state : null;
                                    }),
                                Toggle::make('is_active')
                                    ->inline(false)
                                    ->label(__('app.is_active'))
                                    ->default(true)
                                    ->required(),
                                SpatieMediaLibraryFileUpload::make('icon')
                                    ->translateLabel()
                                    ->disk('cos')
                                    ->openable(true),

                                Fieldset::make(__('app.claimable_titles'))
                                    ->columnSpanFull()
                                    ->schema(function (callable $get, $livewire) {
                                        $record = $livewire->getRecord();

                                        $selectedIds = $record?->claimableTitles()->pluck('claimable_title_id')->toArray() ?? [];

                                        $selectedType = $get('type');
                                        $selectedAmenityType = $get('amenity_type');

                                        $query = ClaimableTitle::query();

                                        if ($selectedType === FacilityAmenityTypeEnum::FACILITY->value) {
                                            $query->where('category', FacilityAmenityTypeEnum::FACILITY->value);
                                        } elseif ($selectedType === FacilityAmenityTypeEnum::AMENITY->value) {
                                            $query->where('category', FacilityAmenityTypeEnum::AMENITY->value)
                                                ->where('type', $selectedAmenityType);
                                        }

                                        $filteredTitles = $query->get();
                                        $isThai = App::getLocale() === 'th';

                                        return $filteredTitles->map(function ($title) use ($selectedIds, $isThai) {
                                            return Toggle::make("claimable_title_{$title->id}")
                                                ->label($isThai ? $title->name_in_thai : $title->name)
                                                ->reactive()
                                                ->afterStateHydrated(function ($component) use ($title, $selectedIds) {
                                                    $component->state(in_array($title->id, $selectedIds));
                                                });
                                        })->toArray();
                                    })
                                    ->visible(function (callable $get) {
                                        $type = $get('type');
                                        $amenityType = $get('amenity_type');

                                        return $type === FacilityAmenityTypeEnum::FACILITY->value || ($type === FacilityAmenityTypeEnum::AMENITY->value && ! is_null($amenityType));
                                    })
                                    ->columns(2),
                            ]),
                    ]),
            ]);
    }
}
