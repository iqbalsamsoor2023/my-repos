<?php

namespace App\Filament\Resources\UnitUsers\Widgets;

use App\Enums\User\Gender;
use App\Policies\UnitUserPolicy;
use App\Services\UnitUserWidgetDataService;
use App\Support\WidgetColorPalette;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class ResidentsGenderStatsOverview extends BaseWidget
{
    use InteractsWithPageTable;

    protected int|string|array $columnSpan = 2;

    protected ?string $pollingInterval = null;

    protected function getColumns(): int
    {
        return 3;
    }

    public static function canView(): bool
    {
        return UnitUserPolicy::hasDashboardAccess(Auth::user());
    }

    protected function getStats(): array
    {
        $user = Auth::user();
        $data = UnitUserWidgetDataService::getScopedDataForUser($user, $this->tableFilters ?? []);
        $colors = WidgetColorPalette::unitUserGenderColors();
        $genderCounts = [
            Gender::MALE->value => (int) ($data['gender_counts'][Gender::MALE->value] ?? 0),
            Gender::FEMALE->value => (int) ($data['gender_counts'][Gender::FEMALE->value] ?? 0),
            'null' => (int) ($data['gender_counts']['null'] ?? 0),
        ];

        $total = (int) ($data['total_residents'] ?? 0);
        $colorMap = [
            Gender::MALE->value => $colors['male'],
            Gender::FEMALE->value => $colors['female'],
            'null' => $colors['not_set'],
        ];
        $labelMap = [
            Gender::MALE->value => 'Male',
            Gender::FEMALE->value => 'Female',
            'null' => __('user.not_yet_set'),
        ];

        return collect($genderCounts)
            ->map(function ($count, $key) use ($total, $colorMap, $labelMap) {
                $percentage = $total > 0 ? round(($count / $total) * 100, 2) : 0;
                $color = $colorMap[$key];

                return Stat::make(
                    new HtmlString("<span style=\"color:{$color}; font-weight:bold;\">{$labelMap[$key]}</span>"),
                    new HtmlString(
                        "
                        <div style=\"display:flex; justify-content:space-between; align-items:center;\">
                            <span style=\"color:{$color}; font-weight:bold;\">{$count}</span>
                            <span style=\"font-size:0.75em;\">({$percentage}%)</span>
                        </div>
                    "
                    )
                )->description("of {$total} resident records");
            })
            ->toArray();
    }
}
