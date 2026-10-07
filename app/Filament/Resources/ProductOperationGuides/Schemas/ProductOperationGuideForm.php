<?php

namespace App\Filament\Resources\ProductOperationGuides\Schemas;

use App\Enums\ResourceMaterial\ResourceTypeEnum;
use App\Models\Erp\Platform;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductOperationGuideForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('POG')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Select::make('platform_id')
                            ->label(__('app.platform'))
                            ->options(Platform::all()->pluck('name', 'id'))
                            ->required()
                            ->reactive(),
                        Hidden::make('type')
                            ->default(ResourceTypeEnum::PRODUCT_OPERATION_GUIDE->value),
                        TextInput::make('title')
                            ->label(__('app.title'))
                            ->required(),
                        TextInput::make('source_link')
                            ->label(__('Source Link'))
                            ->required(),
                    ]),
            ]);
    }
}
