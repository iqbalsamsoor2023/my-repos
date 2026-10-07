<?php

namespace App\Enums\SalesManagement;

use Filament\Support\Contracts\HasLabel;

enum ConstructionProgressEnum: int implements HasLabel
{
    case NOT_YET_CONSTRUCT = 1;
    case PILING = 2;
    case STRUCTURE_WALL = 3;
    case CEILING_FLOORING = 4;
    case ELECTRICITY_DECORATIONS = 5;
    case READY_TO_MOVE_IN = 6;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::NOT_YET_CONSTRUCT => __('sale.construction_progress.not_yet_construct'),
            self::PILING => __('sale.construction_progress.piling'),
            self::STRUCTURE_WALL => __('sale.construction_progress.structure_and_wall'),
            self::CEILING_FLOORING => __('sale.construction_progress.ceiling_and_flooring'),
            self::ELECTRICITY_DECORATIONS => __('sale.construction_progress.electricity_and_decoration'),
            self::READY_TO_MOVE_IN => __('sale.construction_progress.ready_to_move_in'),
            default => 'N/A',
        };
    }

    public function getColor(): ?string
    {
        $defaultColor = '#374151';
        $colorMap = [
            '#EF4444',
            '#3B82F6',
            '#F59E0B',
            '#10B981',
            '#9333EA',
            '#EC4899',
            '#60A5FA',
            '#374151',
        ];

        return match ($this) {
            self::NOT_YET_CONSTRUCT => $colorMap[0],
            self::PILING => $colorMap[1],
            self::STRUCTURE_WALL => $colorMap[2],
            self::CEILING_FLOORING => $colorMap[3],
            self::ELECTRICITY_DECORATIONS => $colorMap[4],
            self::READY_TO_MOVE_IN => $colorMap[5],
            default => $defaultColor,
        };
    }
}
