<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GeoLocationService
{
    public static function getLocationFromIp(?string $ip): string
    {
        if (empty($ip)) {
            return 'Unknown';
        }

        if ($ip === '127.0.0.1' || $ip === '::1') {
            return 'Localhost';
        }

        try {
            $response = Http::timeout(3)->get("http://ip-api.com/json/{$ip}");

            if ($response->successful() && $response->json('status') === 'success') {
                return $response->json('country') . ', ' . $response->json('city');
            }
        } catch (\Exception $e) {
            \Log::error("GeoLocationService error: {$e->getMessage()}");
        }

        return 'Unknown';
    }
}
