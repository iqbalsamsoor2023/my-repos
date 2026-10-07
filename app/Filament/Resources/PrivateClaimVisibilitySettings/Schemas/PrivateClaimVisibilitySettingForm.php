<?php

namespace App\Filament\Resources\PrivateClaimVisibilitySettings\Schemas;

use App\Models\PrivateClaimItem;
use App\Models\PrivateClaimVisibilitySetting;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PrivateClaimVisibilitySettingForm
{
    public static function configure(Schema $schema): Schema
    {
        // $record = $schema->getLivewire()->record;
        // $residenceId = $record?->residence_id;

        $record = $schema->getRecord();
        $residenceId = $record?->id;

        return $schema
            ->components([
                Section::make(__('maintenance.private_claim_items'))
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(3)
                            ->schema(
                                PrivateClaimItem::query()
                                    ->orderBy('name')
                                    ->get()
                                    ->map(function ($item) use ($residenceId) {

                                        $enabled = false;

                                        if ($residenceId) {
                                            $enabled = PrivateClaimVisibilitySetting::where('residence_id', $residenceId)
                                                ->where('private_claim_item_id', $item->id)
                                                ->where('is_enabled', true)
                                                ->exists();
                                        }

                                        $label = app()->getLocale() === 'th' && $item->name_th
                                            ? $item->name_th
                                            : $item->name;

                                        return Toggle::make("items.{$item->id}")
                                            ->label($label)
                                            ->afterStateHydrated(
                                                fn ($component) => $component->state($enabled)
                                            );
                                    })
                                    ->toArray()
                            ),
                    ]),
            ]);
    }
}