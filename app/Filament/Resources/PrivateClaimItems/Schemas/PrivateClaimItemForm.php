<?php

namespace App\Filament\Resources\PrivateClaimItems\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PrivateClaimItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('maintenance.private_claim_item_information'))
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Select::make('private_claim_category_id')
                            ->relationship('privateClaimCategory', 'name') // keep this as fallback
                            ->getOptionLabelFromRecordUsing(function ($record) {
                                return app()->getLocale() === 'th'
                                    ? $record->name_th
                                    : $record->name;
                            })
                            ->label(__('maintenance.private_claim_category')),

                        TextInput::make('name')
                            ->label(__('app.name'))
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
