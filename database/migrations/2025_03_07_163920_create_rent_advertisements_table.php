<?php

use App\Enums\TenancyManagement\TenancyManagementStatus;
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
        Schema::create('rent_advertisements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('residence_id')->index();
            $table->unsignedBigInteger('unit_id')->index();
            $table->bigInteger('rent_price');
            $table->bigInteger('deposit');
            $table->integer('contract_months');
            $table->date('rental_start_date')->nullable();
            $table->enum('tenancy_status', array_column(TenancyManagementStatus::cases(), 'value'));
            $table->boolean('has_custom_rule')->default(false);
            $table->boolean('is_rental_upfront')->default(false);
            $table->boolean('is_active');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rent_advertisements');
    }
};
