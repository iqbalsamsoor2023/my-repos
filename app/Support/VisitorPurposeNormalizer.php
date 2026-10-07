<?php

namespace App\Support;

class VisitorPurposeNormalizer
{
    /**
     * Normalize purpose values to canonical English labels.
     */
    public static function normalize(?string $purpose): ?string
    {
        if ($purpose === null) {
            return null;
        }

        $original = trim($purpose);

        if ($original === '') {
            return null;
        }

        $key = self::normalizeKey($original);

        $map = [
            'รับของ/ส่งของ' => 'Receive & Delivery',
            'รับงาน/ส่งงาน' => 'Receive & Delivery',
            'รับงาน' => 'Receive & Delivery',
            'ส่งงาน' => 'Receive & Delivery',
            'ส่งสินค้า' => 'Receive & Delivery',
            'receive/delivery' => 'Receive & Delivery',
            'receive&delivery' => 'Receive & Delivery',

            'มาติดต่อ' => 'Contact / Business',
            'vip' => 'VIP',

            'รับ-ส่งคน/แท็กซี่' => 'Taxi / Passenger Drop-off',
            'taxi/drop-off' => 'Taxi / Passenger Drop-off',

            'ผู้รับเหมา/คนงาน' => 'Contractor / Worker',
            'ผู้รับเหมาเครื่องจักร' => 'Machine Contractor',

            'ไรเดอร์ส่งอาหาร' => 'Food Delivery',
            'ส่งอาหาร' => 'Food Delivery',
            'ส่งอาหารgrabfood' => 'Food Delivery',

            'รถส่งพาร์ท/deliverytruck' => 'Delivery Truck',
            'ไม่ใช่รถส่งพาร์ทnon-delivery' => 'Non-Delivery Vehicle',

            'อื่นๆ' => 'Others',

            'มาติดต่อ' => 'Visitor Parking',
        ];

        return $map[$key] ?? $original;
    }

    private static function normalizeKey(string $value): string
    {
        $normalized = preg_replace('/\s+/u', ' ', trim($value));
        $normalized = preg_replace('/\s*\/\s*/u', '/', (string) $normalized);
        $normalized = preg_replace('/\s*&\s*/u', '&', (string) $normalized);
        $normalized = str_replace(' ', '', (string) $normalized);

        if (function_exists('mb_strtolower')) {
            return mb_strtolower($normalized, 'UTF-8');
        }

        return strtolower($normalized);
    }
}
