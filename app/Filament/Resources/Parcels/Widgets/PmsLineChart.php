<?php

namespace App\Filament\Resources\Parcels\Widgets;

use App\Enums\Parcel\ParcelStatus;
use App\Policies\ParcelPolicy;
use App\Services\ParcelWidgetDataService;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class PmsLineChart extends ChartWidget
{
    protected ?string $heading = null;

    protected int|string|array $columnSpan = 'full';

    public ?string $filter = '1 month';

    protected ?string $maxHeight = '300px';

    protected ?string $pollingInterval = null;

    public function getHeading(): string
    {
        return __('parcel.incoming_parcels_trend');
    }

    public static function canView(): bool
    {
        if (Route::currentRouteName() === 'filament.pages.dashboard') {
            return false;
        }

        return ParcelPolicy::isPropertyManager(Auth::user());
    }

    protected function getFilters(): ?array
    {
        return [
            '1 week' => 'Last 1 week',
            '2 weeks' => 'Last 2 weeks',
            '1 month' => 'Last 1 month',
        ];
    }

    protected function getData(): array
    {
        $byStatus = ParcelWidgetDataService::getScopedStatusTrendForUser(Auth::user(), $this->filter ?? '1 month');

        if (empty($byStatus)) {
            return [
                'labels' => [],
                'datasets' => [],
            ];
        }

        $allDates = collect($byStatus)
            ->flatMap(fn ($dates) => array_keys($dates))
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        $statusConfig = [
            ParcelStatus::PENDING_PICK_UP->value => [
                'label' => __('parcel.pending_pickup'),
                'color' => '#F59E0B',
            ],
            ParcelStatus::PICKED_UP->value => [
                'label' => __('parcel.picked_up'),
                'color' => '#10B981',
            ],
            ParcelStatus::NOT_MY_PARCEL->value => [
                'label' => __('parcel.not_my_parcel'),
                'color' => '#EF4444',
            ],
        ];

        $datasets = [];
        foreach ($statusConfig as $statusValue => $config) {
            if (! isset($byStatus[$statusValue])) {
                continue;
            }

            $data = array_map(fn ($date) => $byStatus[$statusValue][$date] ?? 0, $allDates);

            $datasets[] = [
                'label' => $config['label'],
                'data' => $data,
                'borderColor' => $config['color'],
                'backgroundColor' => $config['color'],
                'fill' => false,
                'tension' => 0.3,
            ];
        }

        $labels = array_map(fn ($d) => Carbon::parse($d)->format('M d'), $allDates);

        return [
            'labels' => $labels,
            'datasets' => $datasets,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
