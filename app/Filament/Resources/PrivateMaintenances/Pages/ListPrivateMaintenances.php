<?php

namespace App\Filament\Resources\PrivateMaintenances\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\PrivateMaintenances\Widgets\PrivateMaintenancesChart;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Forms\Components\DatePicker;
use App\Enums\Maintenance\MaintenanceStatus;
use App\Enums\Maintenance\WarrantyStatus;
use App\Filament\Resources\PrivateMaintenances\PrivateMaintenanceResource;
use App\Models\Maintenance;
use App\Models\Unit;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ListPrivateMaintenances extends ListRecords
{
    protected static string $resource = PrivateMaintenanceResource::class;

    public function getTitle(): string
    {
        return __('menu.private_maintenances');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('menu.new_private_maintenance')),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            PrivateMaintenancesChart::class,
        ];
    }

    public function getTableFilters(): array
    {
        return [
            SelectFilter::make('residence')
                ->label(__('app.mooban_or_residence'))
                ->options(list_residences())
                ->searchable()
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            $data['value'],
                            fn (Builder $query): Builder => $query->whereHasMorph(
                                'maintainable',
                                Unit::class,
                                function ($subQuery) use ($data) {
                                    return $subQuery->whereHas('moobaan', function ($q) use ($data) {
                                        return $q->whereId($data['value']);
                                    });
                                }
                            ),
                        );
                })
                ->visible(auth()->user()->hasRole(['Super Admin', 'Property Management Operation Center'])),
            Filter::make('unit_number')
                ->schema([
                    TextInput::make('unit_number')
                        ->label(__('unit.unit_number')),
                ])
                ->query(function (Builder $query, array $data) {
                    if (isset($data['unit_number'])) {
                        $query->whereHasMorph(
                            'maintainable',
                            Unit::class,
                            function ($q) use ($data) {
                                return $q->where('unit_number', 'LIKE', '%'.$data['unit_number'].'%');
                            }
                        );
                    }
                }),
            Filter::make('work_order_no')
                ->schema([
                    TextInput::make('maintainable_claim_number')
                        ->label(__('maintenance.work_order_number')),
                ])
                ->query(function (Builder $query, array $data) {
                    if (isset($data['maintainable_claim_number'])) {
                        return $query->where('maintainable_claim_number', $data['maintainable_claim_number']);
                    }
                }),
            Filter::make('reported_by')
                ->schema([
                    TextInput::make('reported_by')
                        ->label(__('app.reported_by')),
                ])
                ->query(function (Builder $query, array $data) {
                    if (isset($data['reported_by'])) {
                        return $query->whereHas('reportedBy', function ($q) use ($data) {
                            return $q->where('name', 'LIKE', '%'.$data['reported_by'].'%');
                        });
                    }
                }),
            SelectFilter::make('maintainable_id')
                ->label(__('maintenance.amenity'))
                ->options(function () {
                    return Maintenance::whereRaw('JSON_VALUE(claimable_item_details, "$.amenity_name") is not null')->groupBy(DB::raw('JSON_VALUE(claimable_item_details, "$.amenity_name")'))->get(DB::raw('JSON_VALUE(claimable_item_details, "$.amenity_name") name'))->pluck('name', 'name');
                })
                ->query(function (Builder $query, array $data) {
                    $value = $data['value'];
                    $query->where('maintainable_type', Unit::class);
                    if ($value) {
                        $query->whereRaw("(
                            JSON_VALUE(claimable_item_details, '$.amenity_name')
                          ) = '{$value}'");
                    }
                }),
            SelectFilter::make('warranty_status')
                ->label(__('maintenance.warranty_status'))
                ->options([
                    WarrantyStatus::WARRANTY->value => __('maintenance.'.strtolower(WarrantyStatus::WARRANTY->name)),
                    WarrantyStatus::EXPIRED->value => __('maintenance.'.strtolower(WarrantyStatus::EXPIRED->name)),
                ])
                ->query(function (Builder $query, array $data) {
                    $value = $data['value'];
                    $query->where('maintainable_type', Unit::class);
                    if ($value) {
                        $query->whereRaw("(
                            select
                              IF(
                                CURDATE() > IF(
                                  JSON_VALUE(m.amenity, '$.period_type') = 'year',
                                  DATE_ADD((select move_in_at from units WHERE id = m.maintainable_id), INTERVAL IF(JSON_VALUE(m.amenity, '$.warranty_period') is not null, JSON_VALUE(m.amenity, '$.warranty_period'), 0) YEAR),
                                  DATE_ADD((select move_in_at from units WHERE id = m.maintainable_id), INTERVAL IF(JSON_VALUE(m.amenity, '$.warranty_period') is not null, JSON_VALUE(m.amenity, '$.warranty_period'), 0) MONTH)
                                ),
                                2,
                                1
                              ) warranty_period
                            from maintenances m
                            where m.id = maintenances.id
                            and m.deleted_at is null
                            limit 1
                          ) = {$value}");
                    }
                }),
            SelectFilter::make('status')
                ->label(__('app.status'))
                ->options([
                    MaintenanceStatus::PENDING->value => __('app.'.strtolower(MaintenanceStatus::PENDING->name)),
                    MaintenanceStatus::IN_PROGRESS->value => __('app.'.strtolower(MaintenanceStatus::IN_PROGRESS->name)),
                    MaintenanceStatus::COMPLETE->value => __('app.completed'),
                ]),
            TernaryFilter::make('is_verified')
                ->label(__('app.is_verified')),
            Filter::make('created_at')
                ->schema([
                    DatePicker::make('report_from')
                        ->label(__('Report From')),
                    DatePicker::make('report_until')
                        ->label(__('Report Until')),
                ])
                ->query(function (Builder $query, array $data) {
                    if (isset($data['report_from']) && isset($data['report_until'])) {
                        return $query
                            ->when(
                                $data['report_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['report_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }
                }),
        ];
    }
}
