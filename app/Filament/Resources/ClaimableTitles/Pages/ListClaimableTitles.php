<?php

namespace App\Filament\Resources\ClaimableTitles\Pages;

use Filament\Actions\CreateAction;
use Filament\Schemas\Components\Tabs\Tab;
use App\Filament\Resources\ClaimableTitles\ClaimableTitleResource;
use App\Models\ClaimableTitle;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListClaimableTitles extends ListRecords
{
    protected static string $resource = ClaimableTitleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $tabs['all'] = Tab::make();

        foreach (ClaimableTitle::get() as $claimableTitle) {
            $tabs[$claimableTitle->category] = Tab::make()->modifyQueryUsing(fn (Builder $query) => $query->where('category', $claimableTitle->category));
        }

        return $tabs;
    }
}
