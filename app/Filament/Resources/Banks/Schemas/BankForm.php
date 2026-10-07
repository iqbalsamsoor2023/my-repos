<?php

namespace App\Filament\Resources\Banks\Schemas;

use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BankForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('bank.bank'))
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label(__('bank.name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('name_th')
                            ->label(__('bank.name_th'))
                            ->required()
                            ->maxLength(255),
                        SpatieMediaLibraryFileUpload::make('logo')
                            ->label(__('bank.logo'))
                            ->collection('bank')
                            ->disk('cos')
                            ->required()
                            ->openable(),
                    ])
            ]);
    }
}
