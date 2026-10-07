<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('visitor_logs_archive', function (Blueprint $table) {
            $table->dropIndex('visitor_logs_query_index');
            $table->dropIndex('visitor_logs_arrival_type_deleted_at_created_at_index');
            $table->dropIndex('visitor_logs_visitor_id_vehicle_plate_no_index');
            $table->dropIndex('visitor_logs_visitor_generated_no_unique');
            $table->dropIndex('visitor_logs_created_at_index');
            $table->dropIndex('idx_created_allowed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visitor_logs_archive', function (Blueprint $table) {
            $table->index(['id', 'deleted_at'], 'visitor_logs_query_index');
            $table->index(
                ['arrival_type', 'deleted_at', 'created_at'],
                'visitor_logs_arrival_type_deleted_at_created_at_index'
            );
            $table->index(
                ['visitor_id', 'vehicle_plate_no'],
                'visitor_logs_visitor_id_vehicle_plate_no_index'
            );
            $table->index('visitor_generated_no', 'visitor_logs_visitor_generated_no_unique');
            $table->index('created_at', 'visitor_logs_created_at_index');
            $table->index(['created_at', 'is_allowed'], 'idx_created_allowed');
        });
    }
};
