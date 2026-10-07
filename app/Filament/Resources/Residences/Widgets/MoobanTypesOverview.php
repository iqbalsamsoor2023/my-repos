<?php

namespace App\Filament\Resources\Residences\Widgets;

use App\Enums\Residence\MoobanType;
use App\Services\ResidenceWidgetDataService;
use App\Traits\ResidenceWidgetFilters;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageTable;

class MoobanTypesOverview extends ChartWidget
{
    use InteractsWithPageTable, ResidenceWidgetFilters;

    protected ?string $heading = 'Mooban Types Overview';

    protected int|string|array $columnSpan = 3;

    protected ?string $maxHeight = '300px';

    protected ?string $pollingInterval = null;

    protected function getData(): array
    {
        $sharedData = ResidenceWidgetDataService::getAllData($this->tableFilters ?? []);
        $moobanTypes = $sharedData['mooban_types'] ?? [];

        $labels = [];
        $data = [];
        $backgroundColors = [];

        foreach ($moobanTypes as $type => $count) {
            $enum = MoobanType::tryFrom((int) $type);

            $labels[] = $enum?->getLabel() ?? 'Unknown';
            $data[] = (int) $count;
            $backgroundColors[] = $this->getColorForType($enum);
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Mooban Types',
                    'data' => $data,
                    'backgroundColor' => $backgroundColors,
                    'borderColor' => $backgroundColors,
                    'borderWidth' => 1,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getColorForType(?MoobanType $type): string
    {
        return match ($type) {
            MoobanType::PUBLIC      => '#3498db', // Blue
            MoobanType::RESIDENCE   => '#27ae60', // Deeper Green
            MoobanType::FACTORY     => '#e67e22', // Burnt Orange
            MoobanType::PMOC        => '#9b59b6', // Softer Purple  
            MoobanType::DEMO_FOR_SG => '#e74c3c', // Red
            default                 => '#95a5a6', // Gray (Unknown)
        };
    }

    public function updatedTableFilters(): void
    {
        $this->emitSelf('refresh');
    }
}
