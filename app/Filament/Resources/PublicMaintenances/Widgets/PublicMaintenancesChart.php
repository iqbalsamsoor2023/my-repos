<?php

namespace App\Filament\Resources\PublicMaintenances\Widgets;

use App\Enums\Maintenance\MaintenanceStatus;
use App\Models\Maintenance;
use App\Models\ResidenceAmenity;
use App\Models\ResidenceAmenityOption;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

class PublicMaintenancesChart extends ChartWidget
{
    public function getHeading(): string
    {
        return __('Public Maintenances');
    }

    protected int|string|array $columnSpan = 'full';

    public ?string $filter = '1 month';

    protected ?string $maxHeight = '300px';

    protected ?string $pollingInterval = '60s';

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
        $startDate = Carbon::now()->sub($this->filter)->toDateString();

        // Base query with date filter
        $maintenances = Maintenance::query()
            ->where('created_at', '>=', $startDate);

        if ($user->hasRole('Property Management')) {
            $residenceId = $user->propertyManagement->id;

            // Only filter IDs at the database level to avoid N+1
            $maintenances = $maintenances->where(function (Builder $query) use ($residenceId) {
                $query->whereHasMorph(
                    'maintainable',
                    [ResidenceAmenity::class],
                    fn(Builder $q) => $q->where('residence_id', $residenceId)
                )->orWhereHasMorph(
                    'maintainable',
                    [ResidenceAmenityOption::class],
                    fn(Builder $q) => $q->whereHas('residenceAmenity', fn($r) => $r->where('residence_id', $residenceId))
                );
            });
        }

        // Group counts by status directly in the query (no eager load needed)
        $reports = $maintenances
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $result = [];
        foreach (MaintenanceStatus::cases() as $status) {
            $result[] = [
                'status' => $status->label(),
                'total' => $reports[$status->value] ?? 0,
            ];
        }

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