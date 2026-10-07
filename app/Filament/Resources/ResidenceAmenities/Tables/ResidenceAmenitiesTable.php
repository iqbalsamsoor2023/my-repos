<?php

namespace App\Filament\Resources\ResidenceAmenities\Tables;

use App\Enums\FacilityAndAmenity\FacilityAmenityTypeEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ResidenceAmenitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('residence.name')
                    ->label(__('app.mooban_or_residence'))
                    ->toggleable()
                    ->description(fn(Model $record): string => $record?->residence?->name_th ?? '-')
                    ->hidden(auth()->user()->hasRole(['Property Management'])),
                TextColumn::make('facilityAndAmenity.type')
                    ->label(__('app.type')),
                TextColumn::make('facilityAndAmenity.name')
                    ->description(fn(Model $record): string => $record?->facilityAndAmenity?->name_in_thai ?? '-')
                    ->label(__('Facility/Amenity')),
                IconColumn::make('is_bookable')
                    ->label(__('app.is_bookable'))
                    ->boolean()
                    ->toggleable(),
                IconColumn::make('is_claimable')
                    ->label(__('app.is_claimable'))
                    ->boolean()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('residence')
                    ->label(__('app.mooban_or_residence'))
                    ->options(list_residences())
                    ->searchable()
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['value'],
                                fn(Builder $query): Builder => $query->where('residence_id', $data['value']),
                            );
                    })
                    ->visible(auth()->user()->hasAnyRole(['Super Admin', 'Admin'])),
                SelectFilter::make('type')
                    ->label(__('app.type'))
                    ->options(FacilityAmenityTypeEnum::options())
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['value'],
                                fn(Builder $query): Builder => $query->whereHas('facilityAndAmenity', function ($q) use ($data) {
                                    return $q->where('type', $data['value']);
                                }),
                            );
                    }),
                TernaryFilter::make('is_active')
                    ->label(__('app.is_active')),
                TernaryFilter::make('is_bookable')
                    ->label(__('app.is_bookable')),
                TernaryFilter::make('is_claimable')
                    ->label(__('app.is_claimable')),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ], position: RecordActionsPosition::BeforeColumns);
    }
}
