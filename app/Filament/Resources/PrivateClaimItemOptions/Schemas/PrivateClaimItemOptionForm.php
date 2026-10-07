<?php

namespace App\Filament\Resources\PrivateClaimItemOptions\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PrivateClaimItemOptionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('app.name'))
                    ->required(),
                TextInput::make('name_th')
                    ->label(__('app.name_th')),
                Toggle::make('is_active')
                    ->label(__('app.is_active'))
                    ->required(),
            ]);
    }
}
