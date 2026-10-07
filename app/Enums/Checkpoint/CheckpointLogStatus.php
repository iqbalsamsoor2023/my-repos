<?php

namespace App\Enums\Checkpoint;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CheckpointLogStatus: int implements HasLabel, HasColor
{
    case PASSED = 1;
    case SKIP = 2;
    case NOT_PASSED = 3;
    case MISS = 4;

    public function getLabel(): string
    {
        return match ($this) {
            self::PASSED => __('checkpoint.statusLabel.passed'),
            self::SKIP => __('checkpoint.statusLabel.skip'),
            self::NOT_PASSED => __('checkpoint.statusLabel.not_passed'),
            self::MISS => __('checkpoint.statusLabel.missed'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PASSED => 'success',
            self::SKIP => 'warning',
            self::NOT_PASSED => 'primary',
            self::MISS => 'danger',
        };
    }
}
