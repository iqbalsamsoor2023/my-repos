<?php

use App\Models\InsuranceCompany;
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
        Schema::table('residences', function (Blueprint $table) {
            $table->dropForeign(['company_insurance_id']);
        });

        Schema::table('residences', function (Blueprint $table) {
            $table->dropColumn('company_insurance_id');
        });

        Schema::table('residences', function (Blueprint $table) {
            $table->foreignIdFor(InsuranceCompany::class)
                ->nullable()
                ->after('guard_house_lane_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('residences', function (Blueprint $table) {
            $table->dropForeign(['insurance_company_id']);
            $table->dropColumn('insurance_company_id');
        });

        Schema::table('residences', function (Blueprint $table) {
            $table->foreignId('company_insurance_id')
                ->nullable()
                ->constrained('companies')
                ->nullOnDelete();
        });
    }
};
