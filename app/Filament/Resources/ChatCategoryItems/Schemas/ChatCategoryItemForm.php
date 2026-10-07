<?php

namespace App\Filament\Resources\ChatCategoryItems\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ChatCategoryItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('app.chat_setting'))
                    ->description(__('app.chat_setting_details'))
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Hidden::make('platform_identifier')
                            ->default('mmb'),
                        Select::make('chat_category_id')
                            ->label(__('support-ticket.category'))
                            ->relationship(
                                name: 'chatCategory',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn ($query) => $query->whereKeyNot(5) // Exclude "Others" category
                            )
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->name} ({$record->name_in_thai})")
                            ->required()
                            ->columnSpanFull(),
                        TextInput::make('title')
                            ->label(__('app.title'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('title_in_thai')
                            ->label(__('app.title_in_thai'))
                            ->required()
                            ->maxLength(255),
                        Toggle::make('is_active')
                            ->label(__('app.is_active'))
                            ->default(true),
                    ])
            ]);
    }
}
