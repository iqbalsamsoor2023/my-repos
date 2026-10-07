<?php

namespace App\Filament\Resources\EmergencyContacts\Schemas;

use App\Enums\EmergencyContact\CoverageMode;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class EmergencyContactForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('user.emergency_contact'))
                    ->description(__('user.emergency_contact_detail'))
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Select::make('department_type')
                            ->label(__('user.department_type'))
                            ->options([
                                'Hospital' => __('app.hospital'),
                                'Police' => __('app.police'),
                                'Foundation' => __('app.foundation'),
                                'Fire Station' => __('app.fire_station'),
                                'Others' => __('app.others'),
                            ])
                            ->searchable()
                            ->reactive()
                            ->required(),
                        TextInput::make('department_type_other')
                            ->label(__('Department Type Other'))
                            ->default('Others')
                            ->hidden(fn(Get $get) => $get('department_type') != 'Others'),
                        TextInput::make('name')
                            ->label(__('app.name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('contact_no')
                            ->label(__('app.phone_number'))
                            ->required()
                            ->maxLength(20),
                        Select::make('coverage_mode')
                            ->label(__('app.coverage_mode'))
                            ->options(CoverageMode::options())
                            ->searchable()
                            ->reactive()
                            ->required(),
                        Select::make('districtEmergencyContacts.district')
                            ->label(__('app.district'))
                            ->multiple()
                            ->options(list_districts())
                            ->hidden(fn(Get $get) => $get('coverage_mode') == CoverageMode::NATIONWIDE->value || $get('coverage_mode') == null),
                        Toggle::make('is_active')
                            ->label(__('app.is_active'))
                            ->inline(false)
                            ->required(),

                    ])
            ]);
    }
}
