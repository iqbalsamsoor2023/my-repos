<?php

namespace App\Filament\Resources\FacilityAndAmenities\Tables;

use App\Enums\FacilityAndAmenity\AmenityTypeEnum;
use App\Models\FacilityAndAmenity;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class FacilityAndAmenitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->query(FacilityAndAmenity::where('name', '!=', 'Others'))
            ->columns([
                SpatieMediaLibraryImageColumn::make('icon')
                    ->label(__('Icon'))
                    ->toggleable(),
                TextColumn::make('name')
                    ->label(__('app.name'))
                    ->searchable(),
                TextColumn::make('name_in_thai')
                    ->label(__('app.name_th'))
                    ->searchable(),
                TextColumn::make('type')
                    ->label(__('app.type')),
                TextColumn::make('amenity_type')
                    ->label(__('Amenity Type'))
                    ->formatStateUsing(fn(?int $state) => AmenityTypeEnum::tryFrom($state)?->label())
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label(__('app.is_active'))
                    ->boolean()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('app.created_at_date'))
                    ->getStateUsing(function (Model $record) {
                        return $record->created_at->format('d-M-y');
                    })
                    ->toggleable(),
                TextColumn::make('created_at_time')
                    ->label(__('app.created_at_time'))
                    ->getStateUsing(function (Model $record) {
                        return $record->created_at->format('H:i:s');
                    })
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('amenity_type')
                    ->label(__('Amenity Type'))
                    ->options(AmenityTypeEnum::options()),
                TernaryFilter::make('is_active')
                    ->label(__('app.is_active'))
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                ]),
            ], position: RecordActionsPosition::BeforeColumns)
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
