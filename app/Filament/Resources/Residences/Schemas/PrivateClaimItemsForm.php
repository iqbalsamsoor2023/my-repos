<?php
namespace App\Filament\Resources\Residences\Schemas;

use App\Models\PrivateClaimItem;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Illuminate\Support\Facades\App;

class PrivateClaimItemsForm
{
    public static function getForm(): array
    {
        return [
            Grid::make(['sm' => 1, 'md' => 2, 'lg' => 2, 'xl' => 2, '2xl' => 2])
                ->schema([
                    Fieldset::make(__('Private Claim Items'))
                        ->schema([
                            ...PrivateClaimItem::get()
                            ->map(function ($privateClaimItem) {
                                return Toggle::make('residence_private_claim_item_'.$privateClaimItem->id)
                                    ->label(App::getLocale() === 'th' ? $privateClaimItem->name_in_thai : $privateClaimItem->name);
                            })
                            ->toArray(),
                        ]),
                ]),
        ];
    }
}