<?php

namespace App\Forms\Components\AmenityBooking;

use Carbon\Carbon;
use Closure;
use Filament\Forms\Components\DateTimePicker as BaseDateTimePicker;

class DateTimePicker extends BaseDateTimePicker
{
    protected string $view = 'forms.components.amenity-booking.date-time-picker';

    public int|string|Closure $focusedMonth = 1;

    protected bool|Closure $isWithoutTime = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->focusedMonth = now()->month;
    }

    public function setFocusedMonth(int|Closure $month)
    {
        $this->focusedMonth = $month;
    }

    public function getFocusedMonth()
    {
        if ($this->isDehydrated() && ($state = $this->getState())) {
            $this->focusedMonth = Carbon::parse($state)->month;
        }

        return $this->evaluate($this->focusedMonth);
    }

    public function hasTime(): bool
    {
        return false;
    }
}
