<?php

namespace App\Enums\Audit;

enum EventTypeEnum: string
{
    case CREATED = 'created';
    case UPDATED = 'updated';
    case DELETED = 'deleted';
    case RESTORED = 'restored';
    case EXPORTED = 'exported';

    public function label(): string
    {
        return $this->value;
    }

    public function color(): string
    {
        return match ($this) {
            self::CREATED => 'green',
            self::UPDATED => 'blue',
            self::DELETED => 'red',
            self::RESTORED => 'yellow',
            self::EXPORTED => 'orange',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
            ->toArray();
    }
}
