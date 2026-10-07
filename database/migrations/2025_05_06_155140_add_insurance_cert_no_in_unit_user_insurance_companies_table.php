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
        Schema::create('insurance_companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_th')->nullable();
            $table->smallInteger('type');
            $table->string('website_url')->nullable();
            $table->boolean('is_active')->default(1);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignIdFor(InsuranceCompany::class)->nullable()->after('country_id');
            $table->string('insurance_policy_no')->nullable()->after('profile_photo_path');
            $table->date('insurance_expiry_date')->nullable()->after('insurance_policy_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop columns added to users table
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['insurance_company_id', 'insurance_policy_no', 'insurance_expiry_date']);
        });

        // Drop the insurance_companies table
        Schema::dropIfExists('insurance_companies');
    }
};
