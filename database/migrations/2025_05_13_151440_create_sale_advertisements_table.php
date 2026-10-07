<?php

use App\Enums\SalesManagement\SellingStatusEnum;
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
        Schema::create('sale_advertisements', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('residence_id')->unsigned();
            $table->bigInteger('unit_id')->unsigned();
            $table->bigInteger('sale_price')->unsigned();
            $table->enum('selling_status', [
                SellingStatusEnum::QUOTATIONS->value,
                SellingStatusEnum::BOOKING->value,
                SellingStatusEnum::CONTRACT->value,
                SellingStatusEnum::LOAN_APPLICATION_STATUS->value,
                SellingStatusEnum::OWNERSHIP_TRANSFER->value,
                SellingStatusEnum::GET_PROMOTIONS->value,
            ]);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_advertisements');
    }
};
