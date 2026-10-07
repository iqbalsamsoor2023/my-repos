<?php

namespace App\Filament\Resources\ChatCategoryItems;

use App\Filament\Resources\ChatCategoryItems\Pages\ListChatCategoryItems;
use App\Filament\Resources\ChatCategoryItems\Schemas\ChatCategoryItemForm;
use App\Filament\Resources\ChatCategoryItems\Tables\ChatCategoryItemsTable;
use App\Models\Erp\ChatCategoryItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ChatCategoryItemResource extends Resource
{
    protected static ?string $model = ChatCategoryItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleBottomCenterText;

    protected static ?int $navigationSort = 15;

    public static function getNavigationGroup(): string
    {
        return __('menu.communication_management');
    }

    public static function getModelLabel(): string
    {
        return __('menu.communicationManagement.manage_chat_category_items');
    }

    public static function getPluralModelLabel(): string
    {
        return __('menu.communicationManagement.manage_chat_category_items');
    }

    public static function getNavigationLabel(): string
    {
        return __('menu.communicationManagement.manage_chat_category_items');
    }

    public static function form(Schema $schema): Schema
    {
        return ChatCategoryItemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ChatCategoryItemsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListChatCategoryItems::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return ChatCategoryItem::with('chatCategory')->where('platform_identifier', 'mmb');
    }
}
