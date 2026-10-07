<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vms_analytics_daily', function (Blueprint $table) {
            $table->id();
            $table->date('summary_date');
            $table->unsignedBigInteger('residence_id')->nullable()->index();

            // Denormalized location fields (from residence → subdistrict → district → province)
            $table->unsignedBigInteger('province_id')->nullable()->index();
            $table->unsignedBigInteger('district_id')->nullable()->index();
            $table->unsignedBigInteger('subdistrict_id')->nullable()->index();

            // Main visitor stats
            $table->unsignedInteger('visitors_in')->default(0);
            $table->unsignedInteger('visitors_out')->default(0);
            $table->unsignedInteger('visitors_remaining')->default(0);
            $table->unsignedInteger('visitors_overnight')->default(0);

            // Arrival type breakdown
            $table->unsignedInteger('drive_in')->default(0);
            $table->unsignedInteger('walk_in')->default(0);
            $table->unsignedInteger('prebook')->default(0);

            // Vehicle type breakdown
            $table->unsignedInteger('vehicle_car')->default(0);
            $table->unsignedInteger('vehicle_truck')->default(0);
            $table->unsignedInteger('vehicle_motorbike')->default(0);
            $table->unsignedInteger('vehicle_van')->default(0);
            $table->unsignedInteger('vehicle_taxi')->default(0);
            $table->unsignedInteger('vehicle_pickup')->default(0);

            // JSON breakdowns
            $table->json('purpose_breakdown')->nullable();
            $table->json('parcel_courier_breakdown')->nullable();
            $table->json('food_delivery_breakdown')->nullable();

            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            // Composite unique: one row per date per residence
            $table->unique(['summary_date', 'residence_id'], 'vms_analytics_daily_date_residence_unique');

            // Performance indexes
            $table->index(['summary_date', 'province_id'], 'idx_vms_date_province');
            $table->index(['summary_date', 'district_id'], 'idx_vms_date_district');
            $table->index(['residence_id', 'summary_date'], 'idx_vms_residence_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vms_analytics_daily');
    }
};
