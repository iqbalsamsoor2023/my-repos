<?php

namespace App\Filament\Resources\AutoSendReports\Schemas;

use App\Filament\Resources\AutoSendReports\Pages\CreateAutoSendReport;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Livewire\Component;

class AutoSendReportForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Auto Send Reports'))
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('email')
                            ->label(__('app.email'))
                            ->email()
                            ->placeholder('example@example.com')
                            ->required()
                            ->maxLength(255),
                        Select::make('residence_id')
                            ->label(__('app.mooban_or_residence'))
                            ->options(function (Component $livewire) {
                                if ($livewire instanceof CreateAutoSendReport) {
                                    return list_create_residences();
                                }

                                return list_residences();
                            })
                            ->required()
                            ->searchable(),
                        TimePicker::make('time')
                            ->label(__('app.time'))
                            ->seconds(false)
                            ->placeholder('HH:MM')
                            ->required(),
                        TextInput::make('hour')
                            ->label(__('app.hour'))
                            ->required()
                            ->numeric()
                            ->default(24)
                            ->minValue(1)
                            ->maxValue(24)
                            ->maxLength(255),
                        Select::make('module_type')
                            ->label(__('app.module_type'))
                            ->options([
                                'Visitor' => 'Visitor',
                                'PGS' => 'PGS',
                                'IRS' => 'IRS',
                            ])
                            ->required(),
                    ])
            ]);
    }
}
