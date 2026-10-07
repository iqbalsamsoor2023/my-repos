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
        Schema::create('visitor_logs_daily_summary', function (Blueprint $table) {
            $table->date('summary_date')->primary();

            // Main Stats
            $table->unsignedInteger('visitors_in')->default(0);
            $table->unsignedInteger('visitors_out')->default(0);
            $table->unsignedInteger('visitors_remaining')->default(0);
            $table->unsignedInteger('visitors_overnight')->default(0);

            // Visitor Type Breakdown
            $table->unsignedInteger('drive_in')->default(0);
            $table->unsignedInteger('walk_in')->default(0);
            $table->unsignedInteger('prebook')->default(0);

            // Vehicle Type Breakdown
            $table->unsignedInteger('vehicle_car')->default(0);
            $table->unsignedInteger('vehicle_truck')->default(0);
            $table->unsignedInteger('vehicle_motorbike')->default(0);
            $table->unsignedInteger('vehicle_van')->default(0);
            $table->unsignedInteger('vehicle_taxi')->default(0);
            $table->unsignedInteger('vehicle_pickup')->default(0);

            // Purpose & Partners stored as JSON for flexibility
            $table->json('purpose_breakdown')->nullable();
            $table->json('parcel_courier_breakdown')->nullable();
            $table->json('food_delivery_breakdown')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('summary_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visitor_logs_daily_summary');
    }
};
