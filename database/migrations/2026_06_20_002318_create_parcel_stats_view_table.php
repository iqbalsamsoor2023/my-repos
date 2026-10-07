<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parcel_stats_view', function (Blueprint $table) {
            $table->unsignedBigInteger('parcel_id')->primary();
            $table->unsignedBigInteger('unit_id')->index();
            $table->unsignedBigInteger('residence_id')->index();
            $table->unsignedBigInteger('property_management_user_id')->nullable()->index();

            $table->unsignedBigInteger('courier_id')->nullable()->index();
            $table->string('courier_name')->nullable()->index();

            $table->unsignedTinyInteger('status')->nullable()->index();

            $table->unsignedSmallInteger('residence_activation_status_id')->nullable()->index();
            $table->unsignedTinyInteger('mooban_type')->nullable()->index();
            $table->unsignedTinyInteger('sub_type')->nullable()->index();
            $table->unsignedBigInteger('subdistrict_id')->nullable()->index();
            $table->string('main_road')->nullable();

            $table->timestamp('parcel_created_at')->nullable()->index();
            $table->timestamp('parcel_updated_at')->nullable();
            $table->timestamp('pickup_time')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->index(['residence_id', 'status'], 'psv_residence_status_idx');
            $table->index(['property_management_user_id', 'status'], 'psv_pm_status_idx');
            $table->index(['residence_id', 'parcel_created_at'], 'psv_residence_created_idx');
            $table->index(['status', 'parcel_created_at'], 'psv_status_created_idx');
            $table->index(['courier_id', 'residence_id'], 'psv_courier_residence_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parcel_stats_view');
    }
};
