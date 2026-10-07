<?php

namespace App\Filament\Resources\TenancyManagements\Tables;

use App\Enums\TenancyManagement\TenancyManagementStatus;
use App\Enums\User\RoleType;
use App\Models\RentAdvertisement;
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
use Illuminate\Support\HtmlString;

class TenancyManagementsTable
{
    public static function configure(Table $table): Table
    {
        // override the getEloquentQuery method to filter by user roles
        // get only rent advertisements that units are not deleted
        $user = auth()->user();
        $query = RentAdvertisement::with('unit')
        ->whereHas('unit', function (Builder $query) {
            $query->whereNull('deleted_at');
        });

        $residenceIdList = [];

        if ($user->hasRole(RoleType::PROPERTY_MANAGEMENT->value)) {
            $residence = get_residence_by_property_management($user->id);
            $residenceIdList = (array) $residence?->id;
        } elseif ($user->hasRole(RoleType::PROPERTY_MANAGEMENT_OPERATION_CENTER->value)) {
            $residenceIdList = get_residence_by_property_management_operation_center($user->id);
        } elseif ($user->hasRole(RoleType::RESALES_AND_TENANCY_MANAGEMENT->value)) {
            $residenceIdList = get_residence_id_list_by_rtm($user->id);
        }

        if(!$user->isSuperAdmin())
        {
            $query->whereIn('residence_id', $residenceIdList);
        }

        $table->query($query);

        return $table
            ->columns([
                TextColumn::make('residence.name')
                    ->label(__('resales-and-tenancies.residence'). '/' . __('resales-and-tenancies.mooban'))
                    ->toggleable()
                    ->hidden(fn () => $user->hasAnyRole(['Re-sales & Tenancy Management'])),
                TextColumn::make('unit.unit_number')
                    ->label(__('resales-and-tenancies.unit_number'))
                    ->copyable()
                    ->icon('heroicon-o-square-2-stack')
                    ->toggleable(),
                ViewColumn::make('room_vr_link')
                    ->label(__('resales-and-tenancies.room_vr'))
                    ->view('tables.columns.room-vr-link'),
                TextColumn::make('rent_price')
                    ->label(__('resales-and-tenancies.rent_price'))
                    ->formatStateUsing(fn (string $state): string => number_format($state))
                    ->toggleable(),
                TextColumn::make('deposit')
                    ->label(__('resales-and-tenancies.deposit'))
                    ->formatStateUsing(fn (string $state): string => number_format($state))
                    ->toggleable(),
                TextColumn::make('contract_months')
                    ->label(__('resales-and-tenancies.contract_months'))
                    ->toggleable(),
                IconColumn::make('has_custom_rule')
                    ->label(__('resales-and-tenancies.has_custom_rule'))
                    ->toggleable()
                    ->boolean(),
                    TextColumn::make('unit.furnitures')
                    ->label(__('resales-and-tenancies.furniture'))
                    ->formatStateUsing(function (RentAdvertisement $record): HtmlString {
                        return new HtmlString(
                            $record->unit?->furnitures
                                ->map(function($item) {
                                    $nameColumn = app()->getLocale() == 'th' ? "name_th" : "name";
                                    return "{$item->householdItem->$nameColumn} - {$item->quantity}<br/>";
                                })
                                ->implode('')
                        );
                    }),
                TextColumn::make('unit.homeAppliances')
                    ->label(__('resales-and-tenancies.home_appliance'))
                    ->formatStateUsing(function (RentAdvertisement $record): HtmlString {
                        return new HtmlString(
                            $record->unit?->homeAppliances
                                ->map(function($item) {
                                    $nameColumn = app()->getLocale() == 'th' ? "name_th" : "name";
                                    return "{$item->householdItem->$nameColumn} - {$item->quantity}<br/>";
                                })
                                ->implode('')
                        );
                    }),
                TextColumn::make('unit.livingSpaces')
                    ->label(__('resales-and-tenancies.living_space'))
                    ->formatStateUsing(function (RentAdvertisement $record): HtmlString {
                        return new HtmlString(
                            $record->unit?->livingSpaces
                                ->map(function($item) {
                                    $nameColumn = app()->getLocale() == 'th' ? "name_th" : "name";
                                    return "{$item->householdItem->$nameColumn} - {$item->quantity}<br/>";
                                })
                                ->implode('')
                        );
                    }),
                TextColumn::make('tenancy_status')
                    ->label(__('resales-and-tenancies.tenancy_status'))
                    ->formatStateUsing(fn (string $state): string => TenancyManagementStatus::tryFrom($state)->getLabel())
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label(__('resales-and-tenancies.active'))
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label(__('resales-and-tenancies.created_at'))
                    ->toggleable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('tenancy_status')
                    ->label(__('resales-and-tenancies.tenancy_status'))
                    ->options(TenancyManagementStatus::class)
                    ->searchable(),
                SelectFilter::make('residence_id')
                    ->label(__('resales-and-tenancies.residence'). '/' . __('resales-and-tenancies.mooban'))
                    ->options(list_residences())
                    ->searchable(),
                SelectFilter::make('unit_id')
                    ->label(__('resales-and-tenancies.unit_number'))
                    ->relationship('unit', 'unit_number', function (Builder $query) use ($residenceIdList) {
                        if (!$user->isSuperAdmin()) {
                            $query->whereIn('residence_id', $residenceIdList);
                        }
                        $query;
                    })
                    ->searchable(),
                SelectFilter::make('has_custom_rule')
                    ->label(__('resales-and-tenancies.has_custom_rule'))
                    ->options([
                        1 => __('resales-and-tenancies.yes'),
                        0 => __('resales-and-tenancies.no'),
                    ]),
                SelectFilter::make('is_rental_upfront')
                    ->label(__('resales-and-tenancies.is_rental_upfront'))
                    ->options([
                        1 => __('resales-and-tenancies.yes'),
                        0 => __('resales-and-tenancies.no'),
                    ]),
                SelectFilter::make('is_active')
                    ->label(__('resales-and-tenancies.active'))
                    ->options([
                        1 => __('resales-and-tenancies.yes'),
                        0 => __('resales-and-tenancies.no'),
                    ]),
                Filter::make('rent_price_from')
                    ->schema([
                        TextInput::make('rent_price_from')
                            ->label(__('resales-and-tenancies.rent_price_from')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['rent_price_from'],
                                fn (Builder $query): Builder => $query->where('rent_price', '>=',$data['rent_price_from']),
                            );
                    }),
                Filter::make('rent_price_to')
                    ->schema([
                        TextInput::make('rent_price_to')
                            ->label(__('resales-and-tenancies.rent_price_to')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['rent_price_to'],
                                fn (Builder $query): Builder => $query->where('rent_price', '<=',$data['rent_price_to']),
                            );
                    }),
                Filter::make('deposit_from')
                    ->schema([
                        TextInput::make('deposit_from')
                            ->label(__('resales-and-tenancies.deposit_from')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['deposit_from'],
                                fn (Builder $query): Builder => $query->where('deposit', '>=',$data['deposit_from']),
                            );
                    }),
                Filter::make('deposit_to')
                    ->schema([
                        TextInput::make('deposit_to')
                            ->label(__('resales-and-tenancies.deposit_to')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['deposit_to'],
                                fn (Builder $query): Builder => $query->where('deposit', '<=',$data['deposit_to']),
                            );
                    }),


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
            ]);
    }
}
