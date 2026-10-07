<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            // Update visitor_logs_archive table
            try {
                DB::statement('ALTER TABLE visitor_logs_archive DROP INDEX visitor_logs_residence_id_index');
            } catch (\Exception $e) {}
            try {
                DB::statement('ALTER TABLE visitor_logs_archive DROP INDEX idx_residence_created');
            } catch (\Exception $e) {}

            DB::statement('ALTER TABLE visitor_logs_archive MODIFY residence_id BIGINT UNSIGNED NULL');
            DB::statement('CREATE INDEX visitor_logs_residence_id_index ON visitor_logs_archive (residence_id)');
            DB::statement('CREATE INDEX idx_residence_created ON visitor_logs_archive (residence_id, created_at)');
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            DB::statement('ALTER TABLE visitor_logs_archive DROP INDEX visitor_logs_residence_id_index');
            DB::statement('ALTER TABLE visitor_logs_archive DROP INDEX idx_residence_created');
            DB::statement('ALTER TABLE visitor_logs_archive MODIFY residence_id VARCHAR(255) NULL');
            DB::statement('CREATE INDEX visitor_logs_residence_id_index ON visitor_logs_archive (residence_id)');
            DB::statement('CREATE INDEX idx_residence_created ON visitor_logs_archive (residence_id, created_at)');
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }
};
