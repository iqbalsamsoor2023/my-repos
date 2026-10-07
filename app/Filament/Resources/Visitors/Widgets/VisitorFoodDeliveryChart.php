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

class VisitorFoodDeliveryChart extends ChartWidget
{
    use HasDateFilter, InteractsWithPageTable;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '280px';

    protected ?string $pollingInterval = null;

    public function getHeading(): ?string
    {
        return $this->periodHeading('Food Delivery');
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
        $foodData = VmsAnalyticsQueryService::foodDeliveryBreakdown($resolvedFrom, $resolvedUntil);

        if (empty($foodData) || array_sum($foodData) === 0) {
            return ChartHelper::emptyDoughnut('Food Delivery', 'No food delivery data in this period');
        }

        $top = array_slice($foodData, 0, 11, true);
        $others = array_sum(array_slice($foodData, 11));
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
                    'label' => 'F&B Count',
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
        return array_slice(WidgetColorPalette::visitorFoodDelivery(), 0, min($count, 22));
    }
}
