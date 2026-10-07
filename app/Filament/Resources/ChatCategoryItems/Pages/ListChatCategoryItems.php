<?php

namespace App\Filament\Resources\ChatCategoryItems\Pages;

use Filament\Schemas\Components\Tabs\Tab;
use Filament\Actions\CreateAction;
use App\Filament\Resources\ChatCategoryItems\ChatCategoryItemResource;
use App\Models\Erp\ChatCategory;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListChatCategoryItems extends ListRecords
{
    protected static string $resource = ChatCategoryItemResource::class;

    public function getTabs(): array
    {
        $chatCategories = ChatCategory::whereKeyNot(5)->get();

        $tabLists['all'] = Tab::make();

        foreach ($chatCategories as $chatCategory) {
            $tabLists[$chatCategory->name] = Tab::make()->modifyQueryUsing(function (Builder $query) use ($chatCategory) {
                return $query->where('chat_category_id', $chatCategory->id);
            });
        }

        return $tabLists;
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('menu.create_chat_category_item')),
        ];
    }
}
