<?php

namespace App\Filament\Resources\ResaleManagements\Tables;

use App\Enums\ResalesManagement\ResalesManagementStatus;
use App\Enums\SalesAndTenancies\BankLoanStatusEnum;
use App\Enums\User\RoleType;
use App\Models\ResaleAdvertisement;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\ColumnGroup;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class ResaleManagementsTable
{
    public static function configure(Table $table): Table
    {
        // override the getEloquentQuery method to filter by user roles
        // get only the resales that's units are not deleted
        $user = auth()->user();
        $query = ResaleAdvertisement::with(['unit'])
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

        if (!$user->isSuperAdmin()) {
            $query->whereIn('residence_id', $residenceIdList);
        }

        $table->query($query);

        return $table
            ->columns([
                TextColumn::make('residence.name')
                    ->label(__('app.mooban_or_residence'))
                    ->toggleable()
                    ->hidden(fn() => auth()->user()->hasAnyRole([RoleType::RESALES_AND_TENANCY_MANAGEMENT->value])),
                TextColumn::make('unit.unit_number')
                    ->label(__('unit.unit_number'))
                    ->copyable()
                    ->icon('heroicon-o-square-2-stack')
                    ->searchable()
                    ->toggleable(),
                ViewColumn::make('room_vr_link')
                    ->label(__('resales-and-tenancies.room_vr'))
                    ->view('tables.columns.room-vr-link'),
                IconColumn::make('have_ownership_documents')
                    ->label(__('resales-and-tenancies.have_ownership_documents'))
                    ->toggleable()
                    ->boolean(),
                TextColumn::make('resale_price')
                    ->label(__('resales-and-tenancies.resale_price'))
                    ->formatStateUsing(fn(string $state): string => number_format($state))
                    ->toggleable(),
                TextColumn::make('bank_loan_status')
                    ->label(__('resales-and-tenancies.bank_loan_status'))
                    ->formatStateUsing(fn(string $state): string => BankLoanStatusEnum::tryFrom($state)->getLabel())
                    ->toggleable(),
                TextColumn::make('unit.furnitures')
                    ->label(__('resales-and-tenancies.furniture'))
                    ->formatStateUsing(function (ResaleAdvertisement $record): HtmlString {
                        return new HtmlString(
                            $record->unit?->furnitures
                                ->map(function ($item) {
                                    $nameColumn = app()->getLocale() == 'th' ? 'name_th' : 'name';
                                    return "{$item->householdItem->$nameColumn} - {$item->quantity}<br/>";
                                })
                                ->implode('')
                        );
                    }),
                TextColumn::make('unit.homeAppliances')
                    ->label(__('resales-and-tenancies.home_appliance'))
                    ->formatStateUsing(function (ResaleAdvertisement $record): HtmlString {
                        return new HtmlString(
                            $record->unit?->homeAppliances
                                ->map(function ($item) {
                                    $nameColumn = app()->getLocale() == 'th' ? 'name_th' : 'name';
                                    return "{$item->householdItem->$nameColumn} - {$item->quantity}<br/>";
                                })
                                ->implode('')
                        );
                    }),
                TextColumn::make('unit.livingSpaces')
                    ->label(__('resales-and-tenancies.living_space'))
                    ->formatStateUsing(function (ResaleAdvertisement $record): HtmlString {
                        return new HtmlString(
                            $record->unit?->livingSpaces
                                ->map(function ($item) {
                                    $nameColumn = app()->getLocale() == 'th' ? 'name_th' : 'name';
                                    return "{$item->householdItem->$nameColumn} - {$item->quantity}<br/>";
                                })
                                ->implode('')
                        );
                    }),
                TextColumn::make('status')
                    ->label(__('resales-and-tenancies.resale_status'))
                    ->formatStateUsing(fn(string $state): string => ResalesManagementStatus::tryFrom($state)->getLabel())
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label(__('resales-and-tenancies.active_inactive'))
                    ->boolean(),
                ColumnGroup::make(__('app.created_at'), [
                    TextColumn::make('created_at_date')
                        ->label(__('app.date'))
                        ->getStateUsing(function (ResaleAdvertisement $record) {
                            return $record->created_at->format('d-M-y');
                        }),
                    TextColumn::make('created_at_time')
                        ->label(__('app.time'))
                        ->getStateUsing(function (ResaleAdvertisement $record) {
                            return $record->created_at->format('H:i:s');
                        }),
                ]),
                ColumnGroup::make(__('app.updated_at'), [
                    TextColumn::make('updated_at_date')
                        ->label(__('app.date'))
                        ->getStateUsing(function (ResaleAdvertisement $record) {
                            return $record->updated_at->format('d-M-y');
                        }),
                    TextColumn::make('updated_at_time')
                        ->label(__('app.time'))
                        ->getStateUsing(function (ResaleAdvertisement $record) {
                            return $record->updated_at->format('H:i:s');
                        }),
                ]),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('residence_id')
                    ->label(__('resales-and-tenancies.residence') . '/' . __('resales-and-tenancies.mooban'))
                    ->options(list_residences())
                    ->searchable()
                    ->visible(fn() => auth()->user()->hasAnyRole(['Super Admin', 'Admin'])),
                SelectFilter::make('unit_id')
                    ->label(__('resales-and-tenancies.unit_number'))
                    ->relationship('unit', 'unit_number', function (Builder $query) use ($residenceIdList) {
                        if (!auth()->user()->isSuperAdmin()) {
                            $query->whereIn('residence_id', $residenceIdList);
                        }
                        $query;
                    })
                    ->searchable(),
                SelectFilter::make('have_ownership_documents')
                    ->label(__('resales-and-tenancies.have_ownership_documents'))
                    ->options([
                        1 => __('resales-and-tenancies.yes'),
                        0 => __('resales-and-tenancies.no'),
                    ]),
                SelectFilter::make('bank_loan_status')
                    ->label(__('resales-and-tenancies.bank_loan_status'))
                    ->options(BankLoanStatusEnum::class)
                    ->searchable(),
                SelectFilter::make('status')
                    ->label(__('resales-and-tenancies.resale_status'))
                    ->options(ResalesManagementStatus::class)
                    ->searchable(),
                Filter::make('resale_price_from')
                    ->schema([
                        TextInput::make('resale_price_from')
                            ->label(__('resales-and-tenancies.resale_price_from')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['resale_price_from'],
                                fn(Builder $query): Builder => $query->where('resale_price', '>=', $data['resale_price_from']),
                            );
                    }),
                Filter::make('resale_price_to')
                    ->schema([
                        TextInput::make('resale_price_to')
                            ->label(__('resales-and-tenancies.resale_price_to')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['resale_price_to'],
                                fn(Builder $query): Builder => $query->where('resale_price', '<=', $data['resale_price_to']),
                            );
                    }),
                SelectFilter::make('is_active')
                    ->label(__('resales-and-tenancies.active'))
                    ->options([
                        1 => __('resales-and-tenancies.yes'),
                        0 => __('resales-and-tenancies.no'),
                    ]),
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
