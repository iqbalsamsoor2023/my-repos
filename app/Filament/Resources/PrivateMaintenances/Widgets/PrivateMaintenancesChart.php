<?php

namespace App\Filament\Resources\PrivateMaintenances\Widgets;

use App\Enums\Maintenance\MaintenanceStatus;
use App\Models\Maintenance;
use App\Models\Unit;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

class PrivateMaintenancesChart extends ChartWidget
{
    public function getHeading(): string
    {
        return __('Private Maintenances');
    }

    protected int|string|array $columnSpan = 'full';

    public ?string $filter = '1 month';

    protected ?string $maxHeight = '300px';

    protected ?string $pollingInterval = null;

    protected function getFilters(): ?array
    {
        return [
            '1 week' => 'Last 1 week',
            '2 weeks' => 'Last 2 weeks',
            '1 month' => 'Last 1 month',
        ];
    }

    public static function canView(): bool
    {
        if (Route::currentRouteName() === 'filament.pages.dashboard') {
            return false;
        }

        return auth()->user()->hasRole('Property Management');
    }

    protected function getData(): array
    {
        $user = auth()->user();
        $maintenances = Maintenance::query();

        // Filter by time range (based on $this->filter)
        $startDate = match ($this->filter) {
            '1 week' => Carbon::now()->subWeek(),
            '2 weeks' => Carbon::now()->subWeeks(2),
            '1 month' => Carbon::now()->subMonth(),
            default => Carbon::now()->subMonth(),
        };

        $maintenances->where('created_at', '>=', $startDate);

        // If user is Property Management, filter by their residence
        if ($user->hasRole('Property Management')) {
            $residenceId = $user->propertyManagement->id;

            $maintenances->whereHasMorph(
                'maintainable',
                [Unit::class],
                fn (Builder $query) => $query->where('residence_id', $residenceId)
            );
        }

        // Aggregate by status
        $reports = $maintenances
            ->select('status', DB::raw('COUNT(*) AS total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        // Build result ensuring all statuses are present
        $result = collect(MaintenanceStatus::cases())
            ->map(fn ($status) => [
                'status' => $status->label(),
                'total' => $reports[$status->value] ?? 0,
            ])
            ->values()
            ->toArray();

        return [
            'labels' => Arr::pluck($result, 'status'),
            'datasets' => [
                [
                    'label' => 'Status',
                    'data' => Arr::pluck($result, 'total'),
                    'backgroundColor' => [
                        '#666A86', // Pending
                        '#788AA3', // In Progress
                        '#92B6B1', // Complete
                    ],
                    'borderWidth' => 0,
                    'hoverOffset' => 4,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
