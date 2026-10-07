<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units_stats_view', function (Blueprint $table) {
            $table->unsignedBigInteger('unit_id')->primary();
            $table->unsignedBigInteger('residence_id')->index();
            $table->smallInteger('mooban_type')->nullable()->index();
            $table->unsignedSmallInteger('residence_activation_status_id')->nullable()->index();
            $table->unsignedBigInteger('subdistrict_id')->nullable()->index();
            $table->unsignedBigInteger('district_id')->nullable()->index();
            $table->unsignedBigInteger('province_id')->nullable()->index();
            $table->string('unit_number')->nullable()->index();
            $table->smallInteger('sub_type')->nullable()->index();
            $table->smallInteger('house_type')->nullable()->index();
            $table->unsignedSmallInteger('status')->nullable()->index();
            $table->boolean('is_signed_up')->default(false)->index();
            $table->boolean('is_registered_owner')->default(false)->index();
            $table->boolean('is_registered_tenant')->default(false)->index();

            $table->timestamp('synced_at')->nullable();
            $table->timestamp('unit_created_at')->nullable()->index();
            $table->timestamp('unit_updated_at')->nullable()->index();
            $table->timestamps();

            $table->index(['province_id', 'mooban_type']);
            $table->index(['district_id', 'mooban_type']);
            $table->index(['residence_activation_status_id', 'mooban_type'], 'usv_ras_mooban_index');
            $table->index(['residence_id', 'unit_number']);
            $table->index(['residence_id', 'sub_type']);
            $table->index(['residence_id', 'house_type']);
            $table->index(['residence_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units_stats_view');
    }
};
