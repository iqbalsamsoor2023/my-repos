<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('residences', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_th')->nullable();
            $table->tinyInteger('type')->comment('1.Public, 2.Residence');
            $table->tinyInteger('property_type')->nullable()->comment('1.Single Home, 2.Town House, 3.Condominium');
            $table->year('completion_year')->nullable();
            $table->double('latitude');
            $table->double('longitude');
            $table->foreignId('subdistrict_id');
            $table->foreignId('developer_id')->nullable()->constrained('companies');
            $table->foreignId('developer_user_id')->nullable()->constrained('users');
            $table->tinyInteger('property_management_type')->nullable()->comment('1.Personal, 2.Company, 3.Developer');
            $table->foreignId('property_management_id')->nullable()->constrained('companies');
            $table->foreignId('property_management_user_id')->nullable()->constrained('users');
            $table->foreignId('receptionist_user_id')->nullable()->constrained('users');
            $table->foreignId('accountant_user_id')->nullable()->constrained('users');
            $table->foreignId('sgoc_company_id')->nullable();
            $table->foreignId('sgoc_residence_guard_user_id')->nullable();
            $table->foreignId('company_insurance_id')->nullable()->constrained('companies');
            $table->foreignId('company_id')->nullable()->constrained('companies');
            $table->boolean('is_active');
            $table->boolean('is_demo');
            $table->date('subscription_start_date');
            $table->date('subscription_end_date');
            $table->boolean('support_ticket_status')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('residences');
    }
};
