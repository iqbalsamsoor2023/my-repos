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
            /**
             * Add new columns
             */
            $table->unsignedBigInteger('courier_logistic_partner_id')
                ->nullable()
                ->after('visitor_purpose');

            $table->unsignedBigInteger('food_delivery_logistic_partner_id')
                ->nullable()
                ->after('courier_logistic_partner_id');

            // Default sorting + best index for ordering
            $table->index(['created_at', 'id'], 'idx_created_at_id');

            // Residence + date filtering (VERY IMPORTANT)
            $table->index(['residence_id', 'created_at'], 'idx_residence_created');

            // Status + date filtering
            $table->index(['is_allowed', 'created_at'], 'idx_allowed_created');

            // Exact lookups
            $table->index('visitor_generated_no', 'idx_visitor_generated_no');
            $table->index('vehicle_plate_no', 'idx_vehicle_plate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visitor_logs_archive', function (Blueprint $table) {
            $table->dropIndex('idx_created_at_new');
            $table->dropIndex('idx_residence_created');
            $table->dropIndex('idx_allowed_created');
            $table->dropIndex('idx_visitor_generated_no');
            $table->dropIndex('idx_vehicle_plate');
            $table->dropColumn('courier_logistic_partner_id');
            $table->dropColumn('food_delivery_logistic_partner_id');
        });
    }
};
