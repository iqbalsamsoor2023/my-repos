<?php

namespace App\Enums\CheckpointQuestionnaire;

enum AnswerType: int
{
    case PASSED = 1;
    case NOT_PASSED = 2;
    case MISS = 3;
    case SKIP = 4;


    public function getLabel(): ?string
    {
        return match ($this) {
            self::PASSED => __('checkpoint.statusLabel.passed'),
            self::NOT_PASSED => __('checkpoint.statusLabel.not_passed'),
            self::MISS => __('checkpoint.statusLabel.missed'),
            self::SKIP => __('checkpoint.statusLabel.skipped'),
        };
    }
}