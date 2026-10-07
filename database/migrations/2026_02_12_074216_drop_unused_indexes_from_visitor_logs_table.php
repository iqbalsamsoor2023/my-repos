<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            // Add FK for visitor_id
            DB::statement('CREATE INDEX visitor_logs_visitor_id_index ON visitor_logs(visitor_id)');

            // Drop unused indexes
            DB::statement('ALTER TABLE visitor_logs DROP INDEX visitor_logs_visitor_id_vehicle_plate_no_index');
            DB::statement('ALTER TABLE visitor_logs DROP INDEX visitor_logs_query_index');
            DB::statement('ALTER TABLE visitor_logs DROP INDEX idx_visitor_logs_updated_at');

        } catch (\Exception $e) {
            echo "Migration failed: " . $e->getMessage();
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            // Recreate dropped indexes
            DB::statement('ALTER TABLE visitor_logs ADD INDEX visitor_logs_visitor_id_vehicle_plate_no_index (visitor_id, vehicle_plate_no)');
            DB::statement('ALTER TABLE visitor_logs ADD INDEX visitor_logs_query_index (id, deleted_at)');
            DB::statement('ALTER TABLE visitor_logs ADD INDEX idx_visitor_logs_updated_at (updated_at)');

            // drop the single-column index
            DB::statement('DROP INDEX visitor_logs_visitor_id_index ON visitor_logs');

        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }
};