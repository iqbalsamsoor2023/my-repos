<?php

namespace App\Filament\Resources\Visitors\Widgets;

use App\Enums\User\RoleType;
use App\Filament\Resources\Visitors\Widgets\Concerns\HasDateFilter;
use App\Helpers\ChartHelper;
use App\Services\VmsAnalyticsQueryService;
use App\Support\WidgetColorPalette;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Illuminate\Support\Facades\Auth;

class VisitorParcelCourierChart extends ChartWidget
{
    use HasDateFilter, InteractsWithPageTable;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '280px';

    protected ?string $pollingInterval = null;

    public function getHeading(): ?string
    {
        return $this->periodHeading('Parcel Courier');
    }

    public static function canView(): bool
    {
        return Auth::user()?->hasAnyRole([
            RoleType::SUPER_ADMIN->value,
            RoleType::ADMIN->value,
        ]) ?? false;
    }

    protected function getData(): array
    {
        [$resolvedFrom, $resolvedUntil] = $this->resolvedDates();
        $courierData = VmsAnalyticsQueryService::parcelCourierBreakdown($resolvedFrom, $resolvedUntil);

        if (empty($courierData) || array_sum($courierData) === 0) {
            return ChartHelper::emptyDoughnut('Parcel Courier', 'No courier data in this period');
        }

        $top = array_slice($courierData, 0, 22, true);
        $others = array_sum(array_slice($courierData, 22));
        $labels = array_keys($top);
        $counts = array_values($top);

        if ($others > 0) {
            $labels[] = 'Others';
            $counts[] = $others;
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Courier Count',
                    'data' => $counts,
                    'backgroundColor' => $this->generateColors(count($counts)),
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function generateColors(int $count): array
    {
        return array_slice(WidgetColorPalette::visitorParcelCourier(), 0, min($count, 22));
    }
}
