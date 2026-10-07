<?php

namespace App\Filament\Resources\Units\RelationManagers;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use App\Models\HouseholdItem;
use App\Models\Unit;
use App\Tables\Columns\RecordToggler;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class LivingSpaceRelationManager extends RelationManager
{
    protected static string $relationship = 'livingSpaces';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('resales-and-tenancies.living_spaces');
    }

    public static function getModelLabel(): ?string
    {
        return __('resales-and-tenancies.living_space');
    }

    public function toggleRecord($recordId, $state)
    {
        $unit = Unit::where('id', $this->getOwnerRecord()->id)->first();

        if ($state) {
            $unit->householdItems()->attach($recordId, ['quantity' => 1]);
        } else {
            $unit->householdItems()->detach($recordId);
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->query(fn () => HouseholdItem::livingSpace()->with([
                'units' => fn ($q) => $q->where('unit_id', $this->ownerRecord->id),
            ]))
            ->columns([
                TextColumn::make('name')
                    ->label(__('resales-and-tenancies.living_space'))
                    ->formatStateUsing(fn (Model $record): string => app()->getLocale() == 'en' ? $record->name : $record->name_th),
                ToggleColumn::make('status')
                    ->label(__('resales-and-tenancies.status'))
                    ->toggleable()
                    ->onColor('primary') 
                    ->offColor('gray')
                    ->getStateUsing(fn ($record) => $record->units->contains('id', $this->ownerRecord->id))
                    ->updateStateUsing(fn ($record, $state) => $this->toggleRecord($record->id, $state)),
                // RecordToggler::make('status')
                //     ->label(__('resales-and-tenancies.status'))
                //     ->getStateUsing(fn ($record) => count($record->units) > 0 ?  true: false),
                TextInputColumn::make('quantity')
                    ->label(__('resales-and-tenancies.quantity'))
                    ->getStateUsing(fn ($record) => count($record->units) > 0 ? $record->units[0]->pivot->quantity : 0)
                    ->rules([
                        'required',
                        'integer',
                        'min:1',
                    ])
                    ->updateStateUsing(function ($record, $state) {
                        $unit = $this->ownerRecord;
                        $unit->householdItems()->syncWithoutDetaching([
                            $record->id => ['quantity' => $state],
                        ]);
                    })
                    ->disabled(fn ($record) => count($record->units) > 0 ? false : true),
            ])
            ->filters([
                //
            ])
            ->heading(__('resales-and-tenancies.living_space'));
    }
}
