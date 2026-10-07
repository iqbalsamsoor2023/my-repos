<?php

namespace App\Filament\Resources\Furnitures\Schemas;

use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FurnitureForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('resales-and-tenancy.furniture'))
                    ->columnSpanFull()
                    ->schema([
                        Group::make([
                            TextInput::make('name')
                                ->label(__('Name'))
                                ->required(),
                            TextInput::make('name_th')
                                ->label(__('Name (TH)'))
                                ->required(),
                        ])->columns(2),
                        SpatieMediaLibraryFileUpload::make('icon')
                            ->collection('furniture')
                            ->customProperties(['type' => 'furniture'])
                            ->disk('cos')
                            ->openable(),
                        Toggle::make('is_active')
                            ->default(true)
                            ->required(),
                    ])
            ]);
    }
}
