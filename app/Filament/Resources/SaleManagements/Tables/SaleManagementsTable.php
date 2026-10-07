<?php

namespace App\Filament\Resources\SaleManagements\Tables;

use App\Enums\SalesManagement\ConstructionProgressEnum;
use App\Enums\SalesManagement\SellingStatusEnum;
use App\Enums\User\RoleType;
use App\Models\SaleAdvertisement;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SaleManagementsTable
{
    public static function configure(Table $table): Table
    {
        // override the getEloquentQuery method to filter by user roles
        // get only sale advertisements that units are not deleted
        $user = auth()->user();
        $query = SaleAdvertisement::with(['unit'])
            ->whereHas('unit', function (Builder $query) {
                $query;
            });

        $residenceIdList = [];
        // get the residence id list by user role

        if ($user->hasRole('Property Management')) {
            $residence = get_residence_by_property_management($user->id);
            $residenceIdList = (array) $residence?->id;
        } elseif ($user->hasRole('Property Management Operation Center')) {
            $residenceIdList = get_residence_by_property_management_operation_center($user->id);
        } elseif ($user->hasRole('Sales Management')) {
            $residenceIdList = get_residence_id_list_by_sm($user->id);
        }

        if (!$user->isSuperAdmin()) {
            $query->whereIn('residence_id', $residenceIdList);
        }

        $table->query($query);
        return $table
            ->columns([
                TextColumn::make('residence.name')
                    ->label(__('sale.residence') . '/' . __('sale.mooban'))
                    ->searchable()
                    ->sortable()
                    ->hidden(fn() => auth()->user()->hasAnyRole(['Sales Management'])),
                TextColumn::make('unit.unit_number')
                    ->label(__('sale.unit_number'))
                    ->copyable()
                    ->icon('heroicon-o-square-2-stack')
                    ->searchable()
                    ->toggleable(),
                ViewColumn::make('empty_room_vr_link')
                    ->label(__('sale.empty_room_vr_link'))
                    ->view('tables.columns.empty-room-vr-link'),
                ViewColumn::make('sample_room_vr_link')
                    ->label(__('sale.sample_room_vr_link'))
                    ->view('tables.columns.sample-room-vr-link'),
                TextColumn::make('sale_price')
                    ->label(__('sale.sale_price'))
                    ->money('thb')
                    ->sortable(),
                TextColumn::make('selling_status')
                    ->label(__('sale.selling_status'))
                    ->formatStateUsing(fn(string $state): string => SellingStatusEnum::tryFrom($state)->getLabel())
                    ->sortable(),
                TextColumn::make('unit.construction_progress')
                    ->label(__('unit.construction_progress'))
                    ->formatStateUsing(fn(string $state): string => ConstructionProgressEnum::tryFrom($state)->getLabel())
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label(__('sale.active'))
                    ->boolean(),

            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('residence_id')
                    ->label(__('sale.residence'). '/'. __('sale.mooban'))
                    ->options(list_residences())
                    ->searchable()
                    ->visible(fn () => auth()->user()->hasAnyRole([RoleType::SUPER_ADMIN->value, RoleType::ADMIN->value])),
                SelectFilter::make('unit_id')
                    ->label(__('sale.unit_number'))
                    ->relationship('unit', 'unit_number', function(Builder $query) use ($user, $residenceIdList) {
                        if(!$user->isSuperAdmin())
                        {
                            $query->whereIn('residence_id', $residenceIdList);
                        }
                        return $query;
                    })
                    ->searchable(),
                SelectFilter::make('selling_status')
                    ->label(__('sale.selling_status'))
                    ->options(SellingStatusEnum::class)
                    ->multiple(true)
                    ->searchable(),
                Filter::make('sale_price_from')
                    ->schema([
                        TextInput::make('sale_price_from')
                            ->label(__('sale.sale_price_from')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['sale_price_from'],
                                fn (Builder $query): Builder => $query->where('sale_price', '>=',$data['sale_price_from']),
                            );
                    }),
                Filter::make('sale_price_to')
                    ->schema([
                        TextInput::make('sale_price_to')
                            ->label(__('sale.sale_price_to')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['sale_price_to'],
                                fn (Builder $query): Builder => $query->where('sale_price', '<=',$data['sale_price_to']),
                            );
                    }),
                SelectFilter::make('is_active')
                    ->label(__('sale.active'))
                    ->options([
                        1 => 'Yes',
                        0 => 'No',
                    ]),
                Filter::make('available_for_sale')
                    ->label(__('sale.available_for_sale'))
                    ->query(fn (Builder $query): Builder => $query->availableForSale()),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(2)
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                ]),
            ], position: RecordActionsPosition::BeforeColumns)
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('sale.no_sale_management'));
    }
}
