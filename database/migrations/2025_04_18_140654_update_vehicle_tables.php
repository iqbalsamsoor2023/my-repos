<?php

use App\Models\Country;
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
        // Rename 'brands' table to 'vehicle_brands'
        Schema::rename('brands', 'vehicle_brands');

        // Add 'country_id' column to 'vehicle_brands'
        Schema::table('vehicle_brands', function (Blueprint $table) {
            $table->foreignIdFor(Country::class)->nullable()->after('id')->constrained()->nullOnDelete();
        });

        // Add new columns to 'vehicle_models'
        Schema::table('vehicle_models', function (Blueprint $table) {
            // Drop existing foreign key constraint first
            $table->dropForeign(['brand_id']);
            // Rename the column
            $table->renameColumn('brand_id', 'vehicle_brand_id');
            $table->smallInteger('body_type')->nullable()->after('type');
            $table->unsignedInteger('price_min')->nullable()->after('body_type');
            $table->unsignedInteger('price_max')->nullable()->after('price_min');
            $table->year('launched_year')->nullable()->after('price_max');
        });

        // Re-apply the foreign key constraint in a separate Schema call
        Schema::table('vehicle_models', function (Blueprint $table) {
            $table->foreign('vehicle_brand_id')
                ->references('id')
                ->on('vehicle_brands')
                ->cascadeOnDelete();
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('fuel_type')->nullable()->after('insurance_company_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback vehicle_models table changes
        Schema::table('vehicle_models', function (Blueprint $table) {
            $table->dropColumn(['body_type', 'price_range', 'launched_year']);
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn('fuel_type');
        });

        // Drop country_id from vehicle_brands
        Schema::table('vehicle_brands', function (Blueprint $table) {
            $table->dropForeign(['country_id']);
            $table->dropColumn('country_id');
        });

        // Rename back to 'brands'
        Schema::rename('vehicle_brands', 'brands');
    }
};
