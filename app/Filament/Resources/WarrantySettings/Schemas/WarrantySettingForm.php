<?php

namespace App\Filament\Resources\WarrantySettings\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class WarrantySettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('app.residence'))
                    ->columnSpanFull()
                    ->columns(2)
                    ->description(__('app.residence_detail'))
                    ->schema([
                        Fieldset::make(__('app.details'))
                        ->columnSpanFull()
                        ->columns(2)
                            ->schema([
                                Select::make('residence_id')
                                    ->relationship(
                                        'residence',
                                        'name',
                                        function (Builder $query, $get, $record) {
                                            $query->where(function ($q) use ($record) {
                                                $q->doesntHave('warrantySetting');

                                                if ($record?->residence_id) {
                                                    $q->orWhere('id', $record->residence_id);
                                                }
                                            });
                                        }
                                    )
                                    ->searchable()
                                    ->required()
                                    ->visible(fn () => auth()->user()?->hasRole('Super Admin')),
                                SpatieMediaLibraryFileUpload::make('handbook')
                                    ->label(__('maintenance.warranty_handbook'))
                                    ->collection('document')
                                    ->customProperties(['attachment' => 'warranty_handbook'])
                                    ->acceptedFileTypes(['application/pdf'])
                                    ->disk('cos')
                                    ->openable(true)
                                    ->downloadable(true),
                                Toggle::make('has_other_option')
                                    ->label(__('maintenance.other_enabled'))
                                    ->reactive()
                                    ->inline(false),
                                Textarea::make('remark')
                                    ->label(__('app.remark'))
                                    ->required(fn (Get $get) => $get('has_other_option') == true)
                                    ->reactive()
                                    ->maxLength(255)
                                    ->visible(fn (Get $get) => $get('has_other_option') == true),
                                Toggle::make('has_warranty_reminder')
                                    ->label(__('maintenance.warranty_expiry_alert'))
                                    ->reactive()
                                    ->inline(false),
                                TextInput::make('reminder_day')
                                    ->label(__('maintenance.remind_day'))
                                    ->numeric()
                                    ->suffix('days before expiry')
                                    ->visible(fn (Get $get) => $get('has_warranty_reminder') == true),
                                Toggle::make('has_appointment_schedule')
                                    ->label(__('maintenance.appointment_datetime_warranty'))
                                    ->required()
                                    ->helperText(__('maintenance.appointment_datetime_warranty_helper')),
                                Toggle::make('has_verification')
                                    ->label(__('maintenance.need_resident_verify_or_rate'))
                                    ->required()
                                    ->helperText(__('maintenance.resident_verify_helper')),
                            ]),

                        //Select::make('residence_id')
                        //     ->label(__('app.mooban_or_residence'))
                        //     ->options(list_residences())
                        //     ->dehydrated(false)
                        //     ->searchable(),
                        //Toggle::make('has_other_option')
                        //     ->required(),
                        //TextInput::make('remark')
                        //     ->maxLength(255),
                        //Toggle::make('has_warranty_reminder')
                        //     ->required(),
                        //TextInput::make('reminder_day')
                        //     ->numeric(),
                        //Toggle::make('has_appointment_schedule')
                        //     ->required(),
                        //Toggle::make('has_verification')
                        //     ->required(),
                    ])
            ]);
    }
}
