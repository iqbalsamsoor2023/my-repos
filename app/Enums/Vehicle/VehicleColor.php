<?php

namespace App\Enums\Vehicle;

enum VehicleColor: string
{
    case WHITE = '#ffffff';
    case BLACK = '#000000';
    case GRAY = '#999999';
    case BLUE = '#2196F3';
    case RED = '#ed1c24';
    case GREEN = '#39b54a';
    case YELLOW = '#FFEB3B';
    case PINK = '#FA6666';
    case PURPLE = '#5822D8';
    case ORANGE = '#FF5722';
    case BROWN = '#6E311E';

    public function label(): string
    {
        return match ($this) {
            self::WHITE => 'ขาว (White)',
            self::BLACK => 'ดำ (Black)',
            self::GRAY => 'เทา (Gray)',
            self::BLUE => 'น้ำเงิน (Blue)',
            self::RED => 'แดง (Red)',
            self::GREEN => 'เขียว (Green)',
            self::YELLOW => 'เหลือง (Yellow)',
            self::PINK => 'ชมพู (Pink)',
            self::PURPLE => 'ม่วง (Purple)',
            self::ORANGE => 'ส้ม (Orange)',
            self::BROWN => 'น้ำตาล (Brown)',
        };
    }

    public function colorCode(): string
    {
        return $this->value;
    }
}
