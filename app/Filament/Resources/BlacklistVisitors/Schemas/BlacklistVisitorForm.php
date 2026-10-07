<?php

namespace App\Filament\Resources\BlacklistVisitors\Schemas;

use App\Enums\Visitor\IdType;
use App\Filament\Resources\BlacklistVisitors\Pages\CreateBlacklistVisitor;
use App\Filament\Resources\Vms\RelationManagers\BlacklistedVisitorsRelationManager;
use App\Rules\BlacklistVistor;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Livewire\Component;

class BlacklistVisitorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Blacklist visitor'))
                    ->description(__('Blacklisted Detail'))
                    ->columnSpanFull()
                    ->schema([
                        Fieldset::make(__('app.residence'))
                            ->schema([
                                Select::make('residence_id')
                                    ->label(__('app.mooban_or_residence'))
                                    ->relationship('residence', 'name')
                                    ->searchable()
                                    ->required(),
                            ])
                            ->columns(1)
                            ->hidden(fn(Component $livewire): bool => $livewire instanceof BlacklistedVisitorsRelationManager),
                        Fieldset::make(__('Visitor Details'))
                            ->schema([
                                Radio::make('id_type')
                                    ->label(__('Nationality'))
                                    ->options([
                                        IdType::IC->value => 'Citizen',
                                        IdType::PASSPORT->value => 'Foreigner',
                                    ])
                                    ->required()
                                    ->inline(true)
                                    ->reactive(),
                                TextInput::make('name')
                                    ->label(__('app.name'))
                                    ->required(),
                                TextInput::make('id_number')
                                    ->label(__('Thai ID'))
                                    ->minLength(13)
                                    ->maxLength(13)
                                    ->rules([
                                        function (Component $livewire) {
                                            if ($livewire instanceof CreateBlacklistVisitor) {
                                                return new BlacklistVistor();
                                            }
                                        },
                                    ])
                                    ->required(fn(Get $get) => $get('id_type') == IdType::IC->value)
                                    ->hidden(fn(Get $get) => $get('id_type') == IdType::PASSPORT->value || $get('id_type') == null),
                                TextInput::make('id_number')
                                    ->label(__('Passport Number'))
                                    ->minLength(1)
                                    ->maxLength(20)
                                    ->rules([
                                        function (Component $livewire) {
                                            if ($livewire instanceof CreateBlacklistVisitor) {
                                                return new BlacklistVistor();
                                            }
                                        },
                                    ])
                                    ->required(fn(Get $get) => $get('id_type') == IdType::PASSPORT->value)
                                    ->hidden(fn(Get $get) => $get('id_type') == IdType::IC->value || $get('id_type') == null),
                                TextInput::make('vehicle_plate_no')
                                    ->label(__('Vehicle Plate Number')),
                                SpatieMediaLibraryFileUpload::make('image')
                                    ->translateLabel()
                                    ->required()
                                    ->collection('blacklist_visitor')
                                    ->disk('cos')
                                    ->image()
                                    ->openable(true),
                                Textarea::make('blacklist_remark')
                                    ->label(__('Remark'))
                                    ->rows(5)
                                    ->cols(5)
                                    ->maxLength(255),
                            ]),
                    ])
            ]);
    }
}
