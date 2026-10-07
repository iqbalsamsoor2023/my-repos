<?php

namespace App\Filament\Resources\Applications\Schemas;

use App\Filament\Resources\Applications\Pages\EditApp;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Livewire\Component;

class ApplicationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('app.app_version'))
                    ->description(__('app.app_version_detail'))
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Select::make('platform')
                            ->options([
                                'IOS' => __('IOS'),
                                'Android' => __('Android'),
                                'Huawei' => __('Huawei'),
                            ])
                            ->reactive()
                            ->required()
                            ->disabled(fn(Component $livewire) => $livewire instanceof EditApp),
                        TextInput::make('application_name')
                            ->label(__('app.application_name'))
                            ->required()
                            ->maxLength(255)
                            ->disabled(fn(Component $livewire) => $livewire instanceof EditApp),
                        TextInput::make('package_identifier')
                            ->label(__('app.package_identifier'))
                            ->required()
                            ->maxLength(255)
                            ->disabled(fn(Component $livewire) => $livewire instanceof EditApp),
                    ])
            ]);
    }
}
