<?php

namespace App\Filament\Resources\PrivateClaimItemTitles\Schemas;

use App\Models\PrivateClaimItem;
use App\Models\PrivateClaimItemOption;
use App\Models\PrivateClaimItemTitle;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PrivateClaimItemTitleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('maintenance.private_claim_item_title_information'))
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Select::make('private_claim_item_id')
                            ->label(__('maintenance.private_claim_item'))
                            ->options(function () {

                                // Get item IDs already used in titles table
                                $usedItemIds = PrivateClaimItemTitle::pluck('private_claim_item_id')
                                    ->unique()
                                    ->toArray();

                                // Return only items NOT already used
                                return PrivateClaimItem::whereNotIn('id', $usedItemIds)
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->required(),
                        CheckboxList::make('options')
                            ->label(__('maintenance.private_claim_item_title'))
                            ->options(
                                PrivateClaimItemOption::where('is_active', true)
                                    ->get()
                                    ->pluck(
                                        app()->getLocale() === 'th' ? 'name_th' : 'name',
                                        'id'
                                    )
                                    ->toArray()
                            )
                            ->columns(2),

                    ])
                    ->columns(1),
            ]);
    }
}
