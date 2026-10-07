<?php

namespace App\Filament\Resources\PrivateClaimCategories\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PrivateClaimCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('maintenance.category_information'))
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label(__('app.name_en'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('name_th')
                            ->label(__('app.name_th'))
                            ->maxLength(255),
                    ])
                    ->columns(2),
            ]);
    }
}
