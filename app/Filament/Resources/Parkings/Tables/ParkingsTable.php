<?php

namespace App\Filament\Resources\Parkings\Tables;

use App\Enums\Parking\DiscountType;
use App\Enums\Parking\ParkingType;
use App\Enums\Parking\RateMode;
use App\Models\Parking;
use App\Services\FilamentExport\FilamentExportBulkAction;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\ColumnGroup;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ParkingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('residence.name')
                    ->label(__('app.mooban_or_residence'))
                    ->toggleable(),
                TextColumn::make('type')
                    ->label(__('app.type'))
                    ->badge()
                    ->formatStateUsing(function (string $state): string {
                        if ($state == ParkingType::FREE->value) {
                            return __('visitor.' . strtolower(ParkingType::FREE->name));
                        } elseif ($state == ParkingType::PAID->value) {
                            return __(ucfirst(strtolower(ParkingType::PAID->name)));
                        } else {
                            return '-';
                        }
                    })
                    ->color(static function ($state): string {
                        if ($state == ParkingType::FREE->value) {
                            return 'primary';
                        } elseif ($state == ParkingType::PAID->value) {
                            return 'success';
                        } else {
                            return '-';
                        }
                    })
                    ->toggleable(),
                TextColumn::make('rate_mode')
                    ->label(__('Rate Mode'))
                    ->badge()
                    ->formatStateUsing(function (string $state): string {
                        if ($state == RateMode::PER_HOUR->value) {
                            return __(str_replace('_', ' ', Str::title(RateMode::PER_HOUR->name)));
                        } elseif ($state == RateMode::PER_DAY->value) {
                            return __(str_replace('_', ' ', Str::title(RateMode::PER_DAY->name)));
                        } else {
                            return '-';
                        }
                    })
                    ->color(static function ($state): string {
                        if ($state == RateMode::PER_HOUR->value) {
                            return 'primary';
                        } elseif ($state == RateMode::PER_DAY->value) {
                            return 'success';
                        } else {
                            return 'default';
                        }
                    })
                    ->toggleable(),
                IconColumn::make('is_discount_coupon')
                    ->label(__('visitor.is_discount_coupon'))
                    ->boolean()
                    ->toggleable(),
                TextColumn::make('discount_type')
                    ->label(__('visitor.discount_type'))
                    ->badge()
                    ->formatStateUsing(function (string $state): string {
                        if ($state == 0) {
                            return __('No Discount');
                        } elseif ($state == 1) {
                            return __('Price based discount');
                        } else {
                            return __('Time based discount');
                        }
                    })
                    ->color(static function ($state): string {
                        if ($state == 1) {
                            return 'primary';
                        } elseif ($state == 2) {
                            return 'success';
                        } else {
                            return 'danger';
                        }
                    })
                    ->toggleable(),
                ColumnGroup::make(__('app.created_at'), [
                    TextColumn::make('created_at_date')
                        ->label(__('app.date'))
                        ->getStateUsing(function (Parking $record) {
                            return $record->created_at->format('d-M-y');
                        }),
                    TextColumn::make('created_at_time')
                        ->label(__('app.time'))
                        ->getStateUsing(function (Parking $record) {
                            return $record->created_at->format('H:i:s');
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Filter::make('residence')
                    ->schema([
                        TextInput::make('residence')
                            ->label(__('app.mooban_or_residence')),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (isset($data['residence'])) {
                            return $query->whereHas('residence', function ($q) use ($data) {
                                return $q->where('name', 'LIKE', '%' . $data['residence'] . '%')
                                    ->orWhere('name_th', 'LIKE', '%' . $data['residence'] . '%');
                            });
                        }
                    })
                    ->visible(auth()->user()->hasRole(['Super Admin', 'Property Management Operation Center'])),
                SelectFilter::make('type')
                    ->label(__('app.type'))
                    ->options([
                        ParkingType::FREE->value => __('visitor.' . strtolower(ParkingType::FREE->name)),
                        ParkingType::PAID->value => Str::title(ParkingType::PAID->name),
                    ]),
                SelectFilter::make('rate_mode')
                    ->label(__('Rate Mode'))
                    ->options([
                        RateMode::PER_HOUR->value => str_replace('_', ' ', Str::title(RateMode::PER_HOUR->name)),
                        RateMode::PER_DAY->value => str_replace('_', ' ', Str::title(RateMode::PER_DAY->name)),
                    ]),
                TernaryFilter::make('is_discount_coupon')
                    ->label(__('visitor.is_discount_coupon')),
                SelectFilter::make('discount_type')
                    ->label(__('visitor.discount_type'))
                    ->options([
                        DiscountType::NO_DISCOUNT_COUPON->value => 'No Discount',
                        DiscountType::PRICE->value => 'Price based discount',
                        DiscountType::TIME->value => 'Time based discount',
                    ]),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(3)
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                ]),
            ], position: RecordActionsPosition::BeforeColumns)
            ->toolbarActions([
                FilamentExportBulkAction::make('export')
                    ->fileName('Parking-Report')
                    ->disableAdditionalColumns()
                    ->disableCsv()
                    ->disablePdf(),
            ]);
    }
}
