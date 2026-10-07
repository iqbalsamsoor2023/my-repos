<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            // Drop existing foreign keys if present
            try {
                DB::statement('ALTER TABLE visitor_logs DROP FOREIGN KEY fk_visitor_logs_residence_id');
            } catch (\Exception $e) {}

            try {
                DB::statement('ALTER TABLE visitor_logs_archive DROP FOREIGN KEY fk_visitor_logs_archive_residence_id');
            } catch (\Exception $e) {}

            // Update visitor_logs table
            try {
                DB::statement('ALTER TABLE visitor_logs DROP INDEX visitor_logs_residence_id_index');
            } catch (\Exception $e) {}
            try {
                DB::statement('ALTER TABLE visitor_logs DROP INDEX idx_vl_res_created_id');
            } catch (\Exception $e) {}

            DB::statement('ALTER TABLE visitor_logs MODIFY residence_id BIGINT UNSIGNED NULL');
            DB::statement('CREATE INDEX visitor_logs_residence_id_index ON visitor_logs (residence_id)');
            DB::statement('CREATE INDEX idx_vl_res_created_id ON visitor_logs (residence_id, created_at, id)');

            // Add foreign key (visitor_logs_archive cannot have FK due to partitioning)
            DB::statement('ALTER TABLE visitor_logs ADD CONSTRAINT fk_visitor_logs_residence_id FOREIGN KEY (residence_id) REFERENCES residences(id) ON DELETE SET NULL');
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            try {
                DB::statement('ALTER TABLE visitor_logs DROP FOREIGN KEY fk_visitor_logs_residence_id');
            } catch (\Exception $e) {}

            DB::statement('ALTER TABLE visitor_logs DROP INDEX visitor_logs_residence_id_index');
            DB::statement('ALTER TABLE visitor_logs DROP INDEX idx_vl_res_created_id');
            DB::statement('ALTER TABLE visitor_logs MODIFY residence_id VARCHAR(255) NULL');
            DB::statement('CREATE INDEX visitor_logs_residence_id_index ON visitor_logs (residence_id)');
            DB::statement('CREATE INDEX idx_vl_res_created_id ON visitor_logs (residence_id, created_at, id)');
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }
};
