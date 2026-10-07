<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

class VisitorHelper
{
    /**
     * Generate unique visitor number per residence per day.
     *
     * Format: 00001-ymd-0001
     *
     * Last 4 digits = sequential count of visitors per residence per day.
     * Uses insertOrIgnore for row creation + atomic UPDATE with LAST_INSERT_ID()
     * for concurrent-safe increment. No deadlocks, no race conditions.
     */
    public static function generateVisitorNo(int $residenceId): string
    {
        $now = now();
        $dateDisplay = $now->format('ymd');
        $logDate = $now->toDateString();

        // Ensure the row exists (idempotent, safe under concurrency)
        DB::table('visitor_sequences')->insertOrIgnore([
            'residence_id' => $residenceId,
            'log_date' => $logDate,
            'last_sequence' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Atomically increment and capture via LAST_INSERT_ID()
        DB::update('
            UPDATE visitor_sequences
            SET last_sequence = LAST_INSERT_ID(last_sequence + 1), updated_at = NOW()
            WHERE residence_id = ? AND log_date = ?
        ', [$residenceId, $logDate]);

        $sequence = (int) DB::selectOne('SELECT LAST_INSERT_ID() as seq')->seq;

        return sprintf('%05d-%s-%04d', $residenceId, $dateDisplay, $sequence);
    }

    /**
     * Generate unique visitor card ID per residence.
     *
     * Uses insertOrIgnore for row creation + atomic UPDATE with LAST_INSERT_ID()
     * for concurrent-safe increment.
     */
    public static function generateVisitorCardId(int $residenceId): string
    {
        DB::table('visitor_card_sequences')->insertOrIgnore([
            'residence_id' => $residenceId,
            'last_sequence' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::update('
            UPDATE visitor_card_sequences
            SET last_sequence = LAST_INSERT_ID(last_sequence + 1), updated_at = NOW()
            WHERE residence_id = ?
        ', [$residenceId]);

        $sequence = (int) DB::selectOne('SELECT LAST_INSERT_ID() as seq')->seq;

        return str_pad($sequence, 3, '0', STR_PAD_LEFT);
    }
}