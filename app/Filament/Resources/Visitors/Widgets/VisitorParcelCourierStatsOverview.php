<?php

namespace App\Filament\Resources\Visitors\Widgets;

use App\Enums\User\RoleType;
use App\Filament\Resources\Visitors\Widgets\Concerns\HasDateFilter;
use App\Services\VmsAnalyticsQueryService;
use App\Support\WidgetColorPalette;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class VisitorParcelCourierStatsOverview extends StatsOverviewWidget
{
    use HasDateFilter, InteractsWithPageTable;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    protected function getColumns(): int
    {
        return 6;
    }

    public static function canView(): bool
    {
        return Auth::user()?->hasAnyRole([
            RoleType::SUPER_ADMIN->value,
            RoleType::ADMIN->value,
        ]) ?? false;
    }

    public function getHeading(): ?string
    {
        return $this->periodHeading('Parcel Courier');
    }

    protected function getStats(): array
    {
        [$resolvedFrom, $resolvedUntil] = $this->resolvedDates();
        $courierData = VmsAnalyticsQueryService::parcelCourierBreakdown($resolvedFrom, $resolvedUntil);
        $courierData = array_slice($courierData, 0, 22, true);
        $total = array_sum($courierData);

        $colors = WidgetColorPalette::visitorParcelCourier();

        $stats = [];
        $index = 0;

        foreach ($courierData as $name => $count) {
            $percentage = $total > 0 ? round(($count / $total) * 100, 1) : 0;
            $color = $colors[$index];

            $stats[] = Stat::make($name, number_format($count))
                ->description("{$percentage}%")
                ->color(WidgetColorPalette::statColorFromHex($color))
                ->chart([0])
                ->chartColor($color);

            $index++;
        }

        return $stats;
    }
}
