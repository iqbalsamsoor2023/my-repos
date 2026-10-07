<?php

namespace App\Filament\Resources\ResidenceInfos\Widgets;

use App\Enums\Residence\InternetProviderCompany;
use App\Services\ResidenceInfoWidgetDataService;
use App\Support\WidgetColorPalette;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\HtmlString;

class InternetProviderStatsOverview extends BaseWidget
{
    use InteractsWithPageTable;

    protected int|string|array $columnSpan = 2;

    protected function getColumns(): int
    {
        return 2;
    }

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $shared = ResidenceInfoWidgetDataService::getAllData($this->tableFilters ?? []);
        $providerCounts = (array) ($shared['internet_providers'] ?? []);
        $providerColors = WidgetColorPalette::residenceInfoInternetProviderColors();

        $total = array_sum($providerCounts);

        // Colors and labels
        $providers = [];
        foreach (InternetProviderCompany::cases() as $case) {
            $providers[(string) $case->value] = [
                'label' => $case->label(),
                'color' => $providerColors[$case->value] ?? WidgetColorPalette::neutralHex(),
            ];
        }
        $providers['not_set'] = [
            'label' => __('user.not_yet_set'),
            'color' => $providerColors['not_set'],
        ];

        return collect($providers)->map(function ($provider, $id) use ($providerCounts, $total) {
            $count = $providerCounts[$id] ?? 0;
            $percentage = $total > 0 ? round(($count / $total) * 100, 2) : 0;

            return Stat::make(
                new HtmlString("<span style=\"color:{$provider['color']};font-weight:bold;\">{$provider['label']}</span>"),
                $count
            )
                ->value(new HtmlString("
                <div style=\"display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap;\">
                    <span style=\"color:{$provider['color']}; font-weight:bold; font-size:1.75rem;\">{$count}</span>
                </div>
            "))
                ->description(new HtmlString(
                    '<span>
                <strong class="text-black dark:text-white">'.$percentage.'%</strong> of '.$total.'
            </span>'
                ));
        })->toArray();
    }

    public function updatedTableFilters(): void
    {
        $this->emitSelf('refresh');
    }
}
