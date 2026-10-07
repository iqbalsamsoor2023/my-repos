<?php

namespace App\Filament\Resources\BpoSoftwareSuppliers\Schemas;

use App\Enums\Residence\BpoSoftwareEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BpoSoftwareSupplierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('bpo_software.supplier_info'))
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label(__('bpo_software.name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('name_th')
                            ->label(__('bpo_software.name_th'))
                            ->required()
                            ->maxLength(255),
                        Select::make('category')
                            ->label(__('bpo_software.category'))
                            ->options(BpoSoftwareEnum::options())
                            ->required()
                            ->searchable(),
                    ])
            ]);
    }
}
