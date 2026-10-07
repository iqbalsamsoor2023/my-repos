<?php

namespace App\Interfaces;

interface VisitorParkingCalculatorRepositoryInterface
{
    public function calculateParking(
        $visitorLog,
        $parking,
        $visitorParking,
        bool $is_penalty,
        float $discount_value,
        bool $is_stamp,
        int $vehicle_type
    );
}
