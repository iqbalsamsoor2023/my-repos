<?php

namespace App\Filament\Widgets\Default\Vbs;

use App\Enums\Residence\SubType;
use App\Enums\Visitor\VehicleType;
use App\Models\ResidenceActivationStatus;
use App\Models\ResidenceStatsView;
use Carbon\Carbon;
use Filament\Tables\Columns\ColumnGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;

class VisitorBySiteTableWidget extends BaseWidget
{
    public static function canView(): bool
    {
        $user = auth()->user();

        return $user !== null
            && ($user->hasAnyRole(['Super Admin', 'Admin']) ?? false)
            && (request()?->is('*visitor-by-site*') || request()?->routeIs('filament.admin.pages.visitor-by-site'));
    }

    private const PURPOSE_COLUMNS = [
        'Visit' => 'purpose_visit',
        'Receive & Delivery' => 'purpose_receive_delivery',
        'Food Delivery' => 'purpose_food_delivery',
        'Contact / Business' => 'purpose_contact_business',
        'Contractor / Worker' => 'purpose_contractor_worker',
        'Taxi / Passenger Drop-off' => 'purpose_taxi_dropoff',
        'Delivery Truck' => 'purpose_delivery_truck',
        'VIP' => 'purpose_vip',
        'Others' => 'purpose_others',
    ];

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    public array  $residenceIds        = [];
    public ?int   $provinceId          = null;
    public array  $districtIds         = [];
    public array  $activationStatusIds = [];
    public array  $moobanTypes         = [];
    public array  $subTypes            = [];
    public array  $purposes            = [];
    public ?string $month = null;

    // Date window — always default to current month; not user-configurable.
    public string $dateFrom  = '';
    public string $dateUntil = '';

    public function mount(): void
    {
        [$this->dateFrom, $this->dateUntil] = $this->resolveDateWindow(null);
    }

    #[On('vbs-filters-updated')]
    public function applyFilters(array $filters): void
    {
        $this->activationStatusIds = array_values(array_filter(array_map('intval', array_unique($filters['activation_status_ids'] ?? []))));
        $this->provinceId          = filled($filters['province_id'] ?? null) ? (int) $filters['province_id'] : null;
        $this->districtIds         = array_values(array_filter(array_map('intval', array_unique($filters['district_ids'] ?? []))));
        $this->residenceIds        = array_values(array_filter(array_map('intval', array_unique($filters['residence_ids'] ?? []))));
        $this->moobanTypes         = array_values(array_filter(array_map('intval', array_unique($filters['mooban_type'] ?? []))));
        $this->subTypes            = array_values(array_filter(array_map('intval', array_unique($filters['sub_type'] ?? []))));
        $this->purposes            = array_values(array_unique(array_filter($filters['purposes'] ?? [])));
        $this->month               = $filters['month'] ?? null;

        [$this->dateFrom, $this->dateUntil] = $this->resolveDateWindow($this->month);

        $this->resetTable();
    }

    public function table(Table $table): Table
    {
        $from        = $this->dateFrom;
        $until       = $this->dateUntil;
        $siteIds     = $this->residenceIds;
        $purposes    = $this->purposes;
        $statusIds   = $this->activationStatusIds;
        $provinceId  = $this->provinceId;
        $districtIds = $this->districtIds;
        $purposeSelectSql = implode(",\n                        ", $this->purposeSelectExpressions());
        $purposeAliasSql = implode(",\n                        ", array_map(
            fn (string $alias): string => "COALESCE(vms_agg.{$alias}, 0) AS {$alias}",
            array_values(self::PURPOSE_COLUMNS)
        ));

        $fromFmt  = Carbon::parse($from)->format('d M Y');
        $untilFmt = Carbon::parse($until)->format('d M Y');

        return $table
            ->heading(__('vbs.heading_range', ['from' => $fromFmt, 'until' => $untilFmt]))
            ->query(function () use ($from, $until, $siteIds, $purposes, $statusIds, $provinceId, $districtIds, $purposeSelectSql, $purposeAliasSql): \Illuminate\Database\Eloquent\Builder {
                // ── Sub-query: aggregate vms_analytics_daily per site ────────
                $vmsAgg = DB::table('vms_analytics_daily')
                    ->selectRaw("
                        residence_id,
                        SUM(visitors_in)                                                     AS total_visitors_in,
                        SUM(visitors_out)                                                    AS total_visitors_out,
                        SUM(drive_in)                                                        AS total_drive_in,
                        SUM(walk_in)                                                         AS total_walk_in,
                        SUM(prebook)                                                         AS total_prebook,
                        SUM(vehicle_motorbike)                                               AS total_motorbike,
                        SUM(vehicle_car)                                                     AS total_car,
                        SUM(vehicle_truck)                                                   AS total_truck,
                        SUM(vehicle_van)                                                     AS total_van,
                        SUM(vehicle_taxi)                                                    AS total_taxi,
                        SUM(vehicle_pickup)                                                  AS total_pickup,
                        {$purposeSelectSql},
                        COUNT(DISTINCT summary_date)                                         AS active_days,
                        ROUND(
                            SUM(visitors_in) / NULLIF(COUNT(DISTINCT summary_date), 0), 1
                        )                                                                    AS avg_daily_visitors,
                        ROUND(
                            SUM(courier_count) / NULLIF(SUM(visitors_in), 0) * 100, 1
                        )                                                                    AS courier_percentage,
                        ROUND(
                            SUM(food_delivery_count) / NULLIF(SUM(visitors_in), 0) * 100, 1
                        )                                                                    AS food_delivery_percentage,
                        MAX(summary_date)                                                    AS last_activity_date
                    ")
                    ->whereBetween('summary_date', [$from, $until])
                    ->when(! empty($siteIds), fn ($q) => $q->whereIn('residence_id', $siteIds))
                    // Purpose filter: only include daily rows where the selected purpose appears in the JSON
                    ->when(! empty($purposes), function ($q) use ($purposes) {
                        $q->where(function ($or) use ($purposes) {
                            foreach ($purposes as $purpose) {
                                $escaped = addslashes($purpose);
                                $or->orWhereRaw('purpose_breakdown LIKE ?', ["%\"{$escaped}\"%"]);
                            }
                        });
                    })
                    ->groupBy('residence_id');

                // ── Main query: residence_stats_view ⋈ vmsAgg ───────────────
                /** @var \Illuminate\Database\Eloquent\Builder $builder */
                $builder = ResidenceStatsView::query()
                    ->leftJoinSub($vmsAgg, 'vms_agg', function ($join) {
                        $join->on('residence_stats_view.residence_id', '=', 'vms_agg.residence_id');
                    })
                    ->select('residence_stats_view.*')
                    ->addSelect(DB::raw("
                        COALESCE(vms_agg.total_visitors_in, 0)        AS total_visitors_in,
                        COALESCE(vms_agg.total_visitors_out, 0)       AS total_visitors_out,
                        COALESCE(vms_agg.total_drive_in, 0)           AS total_drive_in,
                        COALESCE(vms_agg.total_walk_in, 0)            AS total_walk_in,
                        COALESCE(vms_agg.total_prebook, 0)            AS total_prebook,
                        COALESCE(vms_agg.total_motorbike, 0)          AS total_motorbike,
                        COALESCE(vms_agg.total_car, 0)                AS total_car,
                        COALESCE(vms_agg.total_truck, 0)              AS total_truck,
                        COALESCE(vms_agg.total_van, 0)                AS total_van,
                        COALESCE(vms_agg.total_taxi, 0)               AS total_taxi,
                        COALESCE(vms_agg.total_pickup, 0)             AS total_pickup,
                        {$purposeAliasSql},
                        COALESCE(vms_agg.active_days, 0)              AS active_days,
                        COALESCE(vms_agg.avg_daily_visitors, 0)       AS avg_daily_visitors,
                        COALESCE(vms_agg.courier_percentage, 0)       AS courier_percentage,
                        COALESCE(vms_agg.food_delivery_percentage, 0) AS food_delivery_percentage,
                        vms_agg.last_activity_date
                    "))
                    // Activation Status filter
                    ->when(! empty($statusIds), fn ($q) => $q->whereIn('residence_stats_view.residence_activation_status_id', $statusIds))
                    // Province filter
                    ->when(! empty($provinceId), fn ($q) => $q->where('residence_stats_view.province_id', $provinceId))
                    // District filter (multiple)
                    ->when(! empty($districtIds), fn ($q) => $q->whereIn('residence_stats_view.district_id', $districtIds))
                    // Mooban / Public type filter (multiple)
                    ->when(! empty($this->moobanTypes), fn ($q) => $q->whereIn('residence_stats_view.mooban_type', $this->moobanTypes))
                    // Residence sub-type filter (multiple)
                    ->when(! empty($this->subTypes), fn ($q) => $q->whereIn('residence_stats_view.sub_type', $this->subTypes));

                return $builder;
            })
            ->defaultSort('total_visitors_in', 'desc')
            ->columns($this->tableColumns())
            ->searchable(false)
            ->paginationMode(PaginationMode::Default)
            ->paginationPageOptions([25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->striped();
    }

    private function tableColumns(): array
    {
        $activationStatusOptions = ResidenceActivationStatus::query()->pluck('status', 'id')->toArray();

        return [
            TextColumn::make('mvp_recommendation')
                ->label(__('vbs.recommendation_label'))
                ->getStateUsing(function ($record): string {
                    $c1 = (int) ($record->units_count ?? 0) > 200;
                    $c2 = (float) ($record->total_visitors_in ?? 0) > 150;
                    $c3 = (float) ($record->food_delivery_percentage ?? 0) > 50;

                    if ($c1 && $c2 && $c3) {
                        return 'mvp';
                    }

                    if ($c1) {
                        return 'guard_kiosk';
                    }

                    return 'review';
                })
                ->formatStateUsing(fn (string $state): string => __('vbs.recommendation_value.' . $state))
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'mvp'         => 'success',
                    'guard_kiosk' => 'warning',
                    default       => 'gray',
                })
                ->toggleable()
                ->sortable(query: fn ($query, $direction) => $query->orderBy(
                    DB::raw('(CASE
                        WHEN residence_stats_view.units_count > 200
                             AND vms_agg.total_visitors_in > 150
                             AND vms_agg.food_delivery_percentage > 50 THEN 1
                        WHEN residence_stats_view.units_count > 200 THEN 2
                        ELSE 3 END)'),
                    $direction
                )),

            TextColumn::make('residence_activation_status_id')
                ->label(__('app.status'))
                ->badge()
                ->formatStateUsing(fn (string $state): string => $activationStatusOptions[(int) $state] ?? '-')
                ->color(function ($state) {
                    return match ((int) $state) {
                        1 => 'gray',
                        2 => 'red',
                        3 => 'pink',
                        4 => 'blue',
                        5 => 'green',
                        6 => 'orange',
                        default => 'black',
                    };
                })
                ->toggleable(),

            TextColumn::make('province_name_en')
                ->description(fn ($record): string => $record->province_name_th ?: ($record->province_name_en ?? '-'))
                ->label(__('app.province'))
                ->copyable()
                ->searchable(query: function ($query, $search) {
                    $query->where('residence_stats_view.province_name_en', 'like', "%{$search}%")
                          ->orWhere('residence_stats_view.province_name_th', 'like', "%{$search}%");
                })
                ->toggleable(),

            TextColumn::make('district_name_en')
                ->description(fn ($record): string => $record->district_name_th ?: ($record->district_name_en ?? '-'))
                ->label(__('app.district'))
                ->copyable()
                ->searchable(query: function ($query, $search) {
                    $query->where('residence_stats_view.district_name_en', 'like', "%{$search}%")
                          ->orWhere('residence_stats_view.district_name_th', 'like', "%{$search}%");
                })
                ->toggleable(),

            TextColumn::make('name')
                ->label(__('residence.mooban_name'))
                ->description(fn ($record): string => $record->name_th ?? '-')
                ->copyable()
                ->searchable(query: function ($query, $search) {
                    $query->where('residence_stats_view.name', 'like', "%{$search}%")
                          ->orWhere('residence_stats_view.name_th', 'like', "%{$search}%");
                })
                ->sortable()
                ->toggleable(),

            TextColumn::make('sub_type')
                ->label(__('vbs.type'))
                ->badge()
                ->formatStateUsing(fn ($state) => SubType::tryFrom((int) $state)?->getLabel() ?? '-')
                ->color('info')
                ->sortable()
                ->toggleable(),

            TextColumn::make('units_count')
                ->label(__('vbs.house_units'))
                ->numeric()
                ->sortable()
                ->badge()
                ->color(fn ($state): string => (int) $state > 200 ? 'success' : 'gray')
                ->description(__('vbs.criteria_units'))
                ->toggleable(),

            TextColumn::make('avg_daily_visitors')
                ->label(__('vbs.avg_daily_visitors'))
                ->getStateUsing(fn ($record) => number_format((float) ($record->avg_daily_visitors ?? 0), 1))
                ->numeric()
                ->sortable(query: fn ($query, $direction) => $query->orderBy('vms_agg.avg_daily_visitors', $direction))
                ->badge()
                ->color(fn ($state): string => (float) $state > 150 ? 'success' : 'gray')
                ->description(__('vbs.criteria_avg_daily'))
                ->toggleable(),

            TextColumn::make('total_visitors_in')
                ->label(__('vbs.visitor_in_total'))
                ->numeric()
                ->sortable(query: fn ($query, $direction) => $query->orderBy('vms_agg.total_visitors_in', $direction))
                ->badge()
                ->color(fn ($state): string => (float) $state > 150 ? 'success' : 'gray')
                ->description(__('vbs.criteria_visitor_in_total'))
                ->toggleable(),

            TextColumn::make('courier_percentage')
                ->label(__('vbs.courier_percentage'))
                ->getStateUsing(fn ($record): string => number_format((float) ($record->courier_percentage ?? 0), 1) . '%')
                ->sortable(query: fn ($query, $direction) => $query->orderBy('vms_agg.courier_percentage', $direction))
                ->badge()
                ->color(fn ($state): string => (float) rtrim($state, '%') > 50 ? 'success' : 'gray')
                ->description(__('vbs.criteria_rider'))
                ->toggleable(),

            TextColumn::make('food_delivery_percentage')
                ->label(__('vbs.food_delivery_percentage'))
                ->getStateUsing(fn ($record): string => number_format((float) ($record->food_delivery_percentage ?? 0), 1) . '%')
                ->sortable(query: fn ($query, $direction) => $query->orderBy('vms_agg.food_delivery_percentage', $direction))
                ->badge()
                ->color(fn ($state): string => (float) rtrim($state, '%') > 50 ? 'success' : 'gray')
                ->description(__('vbs.criteria_rider'))
                ->toggleable(),

            TextColumn::make('active_days')
                ->label(__('vbs.active_days'))
                ->numeric()
                ->sortable(query: fn ($query, $direction) => $query->orderBy('vms_agg.active_days', $direction))
                ->toggleable(),

            ColumnGroup::make(__('vbs.purpose_of_visit'), [
                TextColumn::make('purpose_visit')
                    ->label(__('vbs.purpose.visit'))
                    ->numeric()
                    ->badge()
                    ->color(fn ($state): string => (int) $state > 0 ? 'primary' : 'gray')
                    ->sortable(query: fn ($query, $direction) => $query->orderBy('vms_agg.purpose_visit', $direction))
                    ->toggleable(),

                TextColumn::make('purpose_receive_delivery')
                    ->label(__('vbs.purpose.receive_delivery'))
                    ->numeric()
                    ->badge()
                    ->color(fn ($state): string => (int) $state > 0 ? 'warning' : 'gray')
                    ->sortable(query: fn ($query, $direction) => $query->orderBy('vms_agg.purpose_receive_delivery', $direction))
                    ->toggleable(),

                TextColumn::make('purpose_food_delivery')
                    ->label(__('vbs.purpose.food_delivery'))
                    ->numeric()
                    ->badge()
                    ->color(fn ($state): string => (int) $state > 0 ? 'success' : 'gray')
                    ->sortable(query: fn ($query, $direction) => $query->orderBy('vms_agg.purpose_food_delivery', $direction))
                    ->toggleable(),

                TextColumn::make('purpose_contact_business')
                    ->label(__('vbs.purpose.contact_business'))
                    ->numeric()
                    ->badge()
                    ->color(fn ($state): string => (int) $state > 0 ? 'info' : 'gray')
                    ->sortable(query: fn ($query, $direction) => $query->orderBy('vms_agg.purpose_contact_business', $direction))
                    ->toggleable(),

                TextColumn::make('purpose_contractor_worker')
                    ->label(__('vbs.purpose.contractor_worker'))
                    ->numeric()
                    ->badge()
                    ->color(fn ($state): string => (int) $state > 0 ? 'danger' : 'gray')
                    ->sortable(query: fn ($query, $direction) => $query->orderBy('vms_agg.purpose_contractor_worker', $direction))
                    ->toggleable(),

                TextColumn::make('purpose_taxi_dropoff')
                    ->label(__('vbs.purpose.taxi_dropoff'))
                    ->numeric()
                    ->badge()
                    ->color(fn ($state): string => (int) $state > 0 ? 'secondary' : 'gray')
                    ->sortable(query: fn ($query, $direction) => $query->orderBy('vms_agg.purpose_taxi_dropoff', $direction))
                    ->toggleable(),

                TextColumn::make('purpose_delivery_truck')
                    ->label(__('vbs.purpose.delivery_truck'))
                    ->numeric()
                    ->badge()
                    ->color(fn ($state): string => (int) $state > 0 ? 'warning' : 'gray')
                    ->sortable(query: fn ($query, $direction) => $query->orderBy('vms_agg.purpose_delivery_truck', $direction))
                    ->toggleable(),

                TextColumn::make('purpose_vip')
                    ->label(__('vbs.purpose.vip'))
                    ->numeric()
                    ->badge()
                    ->color(fn ($state): string => (int) $state > 0 ? 'success' : 'gray')
                    ->sortable(query: fn ($query, $direction) => $query->orderBy('vms_agg.purpose_vip', $direction))
                    ->toggleable(),

                TextColumn::make('purpose_others')
                    ->label(__('vbs.purpose.others'))
                    ->numeric()
                    ->badge()
                    ->color(fn ($state): string => (int) $state > 0 ? 'gray' : 'gray')
                    ->sortable(query: fn ($query, $direction) => $query->orderBy('vms_agg.purpose_others', $direction))
                    ->toggleable(),
            ]),

            ColumnGroup::make(__('vehicle.vehicle_type'), [
                TextColumn::make('total_car')
                    ->label(VehicleType::CAR->getLabel())
                    ->numeric()
                    ->badge()
                    ->color(fn ($state): string => (int) $state > 0 ? 'primary' : 'gray')
                    ->sortable(query: fn ($query, $direction) => $query->orderBy('vms_agg.total_car', $direction))
                    ->toggleable(),

                TextColumn::make('total_truck')
                    ->label(VehicleType::TRUCK->getLabel())
                    ->numeric()
                    ->badge()
                    ->color(fn ($state): string => (int) $state > 0 ? 'warning' : 'gray')
                    ->sortable(query: fn ($query, $direction) => $query->orderBy('vms_agg.total_truck', $direction))
                    ->toggleable(),

                TextColumn::make('total_motorbike')
                    ->label(VehicleType::MOTORBIKE->getLabel())
                    ->numeric()
                    ->badge()
                    ->color(fn ($state): string => (int) $state > 0 ? 'success' : 'gray')
                    ->sortable(query: fn ($query, $direction) => $query->orderBy('vms_agg.total_motorbike', $direction))
                    ->toggleable(),

                TextColumn::make('total_van')
                    ->label(VehicleType::VAN->getLabel())
                    ->numeric()
                    ->badge()
                    ->color(fn ($state): string => (int) $state > 0 ? 'info' : 'gray')
                    ->sortable(query: fn ($query, $direction) => $query->orderBy('vms_agg.total_van', $direction))
                    ->toggleable(),

                TextColumn::make('total_taxi')
                    ->label(VehicleType::TAXI->getLabel())
                    ->numeric()
                    ->badge()
                    ->color(fn ($state): string => (int) $state > 0 ? 'danger' : 'gray')
                    ->sortable(query: fn ($query, $direction) => $query->orderBy('vms_agg.total_taxi', $direction))
                    ->toggleable(),

                TextColumn::make('total_pickup')
                    ->label(VehicleType::PICKUP->getLabel())
                    ->numeric()
                    ->badge()
                    ->color(fn ($state): string => (int) $state > 0 ? 'secondary' : 'gray')
                    ->sortable(query: fn ($query, $direction) => $query->orderBy('vms_agg.total_pickup', $direction))
                    ->toggleable(),
            ]),

            // ── Last Seen ─────────────────────────────────────────────────
            TextColumn::make('last_activity_date')
                ->label(__('vbs.last_activity'))
                ->date('d M Y')
                ->sortable(query: fn ($query, $direction) => $query->orderBy('vms_agg.last_activity_date', $direction))
                ->toggleable(),
        ];
    }

    // Column toggles only; filters are handled via the external filter widget

    private function resolveDateWindow(?string $month): array
    {
        if (filled($month)) {
            try {
                $date = Carbon::createFromFormat('Y-m', $month);

                return [$date->startOfMonth()->toDateString(), $date->endOfMonth()->toDateString()];
            } catch (\Throwable) {
                // fall through to current-month default
            }
        }

        return [Carbon::now()->startOfMonth()->toDateString(), Carbon::now()->toDateString()];
    }

    /**
     * Build SQL fragments that aggregate canonical purpose buckets from
     * vms_analytics_daily.purpose_breakdown JSON, consistent with
     * VmsAnalyticsQueryService::purposeBreakdown normalization output.
     */
    private function purposeSelectExpressions(): array
    {
        $expressions = [];

        foreach (self::PURPOSE_COLUMNS as $purpose => $alias) {
            $purposeSql = addslashes($purpose);

            $expressions[] = "SUM(COALESCE((
                            SELECT SUM(jt.cnt)
                            FROM JSON_TABLE(
                                COALESCE(vms_analytics_daily.purpose_breakdown, JSON_ARRAY()),
                                '$[*]' COLUMNS (
                                    purpose VARCHAR(191) PATH '$.purpose',
                                    cnt INT PATH '$.count'
                                )
                            ) AS jt
                            WHERE jt.purpose = '{$purposeSql}'
                        ), 0))                                                           AS {$alias}";
        }

        return $expressions;
    }
}
