<?php

namespace App\Filament\Resources\ResidenceActivationStatuses\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ResidenceActivationStatusForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('residence.activation_status'))
                    ->description(__('residence.residence_activation_status'))
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('status')
                            ->label(__('app.status')),
                    ])
            ]);
    }
}
