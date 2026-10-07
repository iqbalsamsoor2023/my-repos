<?php

use App\Enums\ResalesManagement\ResalesManagementStatus;
use App\Enums\SalesAndTenancies\BankLoanStatusEnum;
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
        Schema::create('resale_advertisements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('residence_id')->index();
            $table->unsignedBigInteger('unit_id')->index();
            $table->boolean('have_ownership_documents')->nullable();
            $table->enum('bank_loan_status', array_column(BankLoanStatusEnum::cases(), 'value'));
            $table->bigInteger('resale_price')->nullable();
            $table->boolean('is_active')->nullable();
            $table->enum('status', array_column(ResalesManagementStatus::cases(), 'value'));
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resale_advertisements');
    }
};
