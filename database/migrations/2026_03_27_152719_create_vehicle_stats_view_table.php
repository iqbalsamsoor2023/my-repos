<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_stats_view', function (Blueprint $table) {
            $table->unsignedBigInteger('vehicle_id')->primary();
            $table->unsignedBigInteger('unit_id')->index();
            $table->unsignedBigInteger('residence_id')->index();
            $table->unsignedBigInteger('property_management_user_id')->nullable()->index();

            $table->unsignedTinyInteger('vehicle_type')->nullable()->index();
            $table->unsignedBigInteger('vehicle_brand_id')->nullable()->index();
            $table->string('vehicle_brand_name')->nullable()->index();
            $table->unsignedTinyInteger('body_type')->nullable()->index();
            $table->unsignedTinyInteger('fuel_type')->nullable()->index();
            $table->unsignedSmallInteger('model_year')->nullable()->index();
            $table->unsignedBigInteger('insurance_company_id')->nullable()->index();
            $table->string('insurance_company_name')->nullable()->index();
            $table->string('insurance_company_name_en')->nullable();

            $table->unsignedSmallInteger('residence_activation_status_id')->nullable()->index();

            $table->unsignedTinyInteger('mooban_type')->nullable()->index();
            $table->unsignedTinyInteger('sub_type')->nullable()->index();
            $table->unsignedBigInteger('subdistrict_id')->nullable()->index();
            $table->string('main_road')->nullable();

            $table->timestamp('vehicle_created_at')->nullable()->index();
            $table->timestamp('vehicle_updated_at')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->index(['vehicle_type', 'vehicle_brand_id']);
            $table->index(['vehicle_type', 'fuel_type']);
            $table->index(['vehicle_type', 'body_type']);
            $table->index(['vehicle_type', 'model_year']);
            $table->index(['vehicle_type', 'insurance_company_id']);
            $table->index(['residence_id', 'vehicle_type']);
            $table->index(['residence_activation_status_id', 'vehicle_type'], 'vsv_ras_type_idx');
            $table->index(['vehicle_type', 'vehicle_created_at'], 'vsv_type_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_stats_view');
    }
};
