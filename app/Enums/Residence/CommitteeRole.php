<?php

namespace App\Enums\Residence;

use Filament\Support\Contracts\HasLabel;

enum CommitteeRole: int implements HasLabel
{
    case PRESIDENT = 1;
    case VICE_PRESIDENT = 2;
    case SECRETARY = 3;
    case TREASURER = 4;
    case MEMBER = 5;

    /**
     * Get the English label for the role.
     */
    public function labelEn(): string
    {
        return match ($this) {
            self::PRESIDENT => 'President',
            self::VICE_PRESIDENT => 'Vice President',
            self::SECRETARY => 'Secretary',
            self::TREASURER => 'Treasurer/Accounting',
            self::MEMBER => 'Committee Member',
        };
    }

    /**
     * Get the Thai label for the role.
     */
    public function labelTh(): string
    {
        return match ($this) {
            self::PRESIDENT => 'ประธาน',
            self::VICE_PRESIDENT => 'รองประธาน',
            self::SECRETARY => 'เลขานุการ',
            self::TREASURER => 'เหรัญญิก / บัญชี',
            self::MEMBER => 'กรรมการ',
        };
    }

    /**
     * Get the bilingual label (Thai + English).
     */
    public function getLabel(): ?string
    {
        return "{$this->labelTh()} ({$this->labelEn()})";
    }

    /**
     * Get all roles as options array for selects.
     *
     * @return array<int, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $role) => [$role->value => $role->getLabel()])
            ->toArray();
    }

    /**
     * Get the color for badges in Filament.
     */
    public function getColor(): string
    {
        return match ($this) {
            self::PRESIDENT => 'success',
            self::VICE_PRESIDENT => 'info',
            self::SECRETARY => 'warning',
            self::TREASURER => 'primary',
            self::MEMBER => 'gray',
        };
    }
}
