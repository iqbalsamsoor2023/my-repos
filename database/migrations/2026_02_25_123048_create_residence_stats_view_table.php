<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('residence_stats_view', function (Blueprint $table) {
            $table->unsignedBigInteger('residence_id')->primary();

            // Core fields (denormalized from residences)
            $table->string('name')->nullable();
            $table->string('name_th')->nullable();
            $table->smallInteger('mooban_type')->nullable()->index();
            $table->smallInteger('sub_type')->nullable()->index();
            $table->unsignedSmallInteger('residence_activation_status_id')->nullable()->index();
            $table->string('activation_status_name', 100)->nullable();
            $table->smallInteger('property_management_type')->nullable()->index();

            // Location (denormalized from thailand_sub_districts/districts/provinces)
            $table->unsignedBigInteger('subdistrict_id')->nullable()->index();
            $table->unsignedBigInteger('district_id')->nullable()->index();
            $table->unsignedBigInteger('province_id')->nullable()->index();
            $table->string('province_name_en', 100)->nullable();
            $table->string('province_name_th', 100)->nullable();
            $table->string('district_name_en', 100)->nullable();
            $table->string('district_name_th', 100)->nullable();
            $table->string('subdistrict_name_en', 100)->nullable();
            $table->string('subdistrict_name_th', 100)->nullable();
            $table->string('main_road')->nullable();
            $table->string('full_address')->nullable();

            // Company info (denormalized)
            $table->unsignedBigInteger('developer_id')->nullable();
            $table->string('developer_name')->nullable();
            $table->unsignedBigInteger('property_management_id')->nullable();
            $table->string('pm_company_name')->nullable();
            $table->unsignedBigInteger('property_management_user_id')->nullable();
            $table->string('pm_user_name')->nullable();
            $table->unsignedBigInteger('sgoc_company_id')->nullable();
            $table->string('sgoc_company_name')->nullable();

            // Pre-computed aggregates
            $table->unsignedInteger('units_count')->default(0);
            $table->unsignedInteger('distinct_user_count')->default(0);
            $table->decimal('sign_up_percentage', 6, 2)->default(0);

            // Infrastructure fields
            $table->unsignedSmallInteger('completion_year')->nullable();
            $table->unsignedSmallInteger('guard_house_entry_number')->nullable();
            $table->string('guard_house_lane_type', 50)->nullable();
            $table->boolean('has_roof')->nullable();
            $table->string('entrance_barrier_type', 50)->nullable();
            $table->json('internet_provider_id')->nullable();
            $table->boolean('has_cctv')->default(false);
            $table->unsignedSmallInteger('cctv_count')->default(0);
            $table->unsignedSmallInteger('security_guard_count')->nullable();

            // Subscription & contract
            $table->date('subscription_start_date')->nullable();
            $table->date('subscription_end_date')->nullable();

            // JSON fields (kept as-is for filter compatibility)
            $table->json('juristic_details')->nullable();
            $table->json('bpo_software_suppliers')->nullable();
            $table->json('person_in_charges')->nullable();

            // Latest invoice service duration (pre-joined)
            $table->string('service_duration_latest', 10)->nullable();

            // Full-text search index
            $table->text('search_index')->nullable();

            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            // Composite indexes for common filter combos
            $table->index(['mooban_type', 'sub_type'], 'idx_rsv_type_subtype');
            $table->index(['mooban_type', 'residence_activation_status_id'], 'idx_rsv_type_status');
            $table->index(['province_id', 'district_id'], 'idx_rsv_province_district');
            $table->index(['mooban_type', 'sub_type', 'residence_activation_status_id'], 'idx_rsv_type_subtype_status');
            $table->index(['mooban_type', 'province_id', 'district_id'], 'idx_rsv_type_province_district');
        });

        // Add fulltext index for search_index
        \Illuminate\Support\Facades\DB::statement(
            'ALTER TABLE residence_stats_view ADD FULLTEXT INDEX idx_rsv_search (search_index)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('residence_stats_view');
    }
};
