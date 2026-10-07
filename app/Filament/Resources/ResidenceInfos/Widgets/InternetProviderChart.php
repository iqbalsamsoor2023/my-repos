<?php

namespace App\Filament\Resources\ResidenceInfos\Widgets;

use App\Enums\Residence\InternetProviderCompany;
use App\Services\ResidenceInfoWidgetDataService;
use App\Support\WidgetColorPalette;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageTable;

class InternetProviderChart extends ChartWidget
{
    use InteractsWithPageTable;

    protected ?string $heading = null;

    protected ?string $pollingInterval = null;

    public function getHeading(): ?string
    {
        return __('residence.internet_providers');
    }

    protected function getData(): array
    {
        $shared = ResidenceInfoWidgetDataService::getAllData($this->tableFilters ?? []);
        $providerCounts = (array) ($shared['internet_providers'] ?? []);
        $providerColors = WidgetColorPalette::residenceInfoInternetProviderColors();

        $providers = collect(InternetProviderCompany::cases())->mapWithKeys(fn (InternetProviderCompany $case): array => [
            (string) $case->value => [
                'label' => $case->label(),
                'color' => $providerColors[$case->value] ?? WidgetColorPalette::neutralHex(),
            ],
        ])->toArray();

        $providers['not_set'] = [
            'label' => __('user.not_yet_set'),
            'color' => $providerColors['not_set'],
        ];

        $labels = [];
        $countsData = [];
        $colors = [];

        foreach ($providers as $id => $provider) {
            $labels[] = $provider['label'];
            $countsData[] = (int) ($providerCounts[$id] ?? 0);
            $colors[] = $provider['color'];
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => __('residence.internet_providers'),
                    'data' => $countsData,
                    'backgroundColor' => $colors,
                    'borderColor' => $colors,
                    'hoverOffset' => 4,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    public function updatedTableFilters(): void
    {
        $this->emitSelf('refresh');
    }
}
