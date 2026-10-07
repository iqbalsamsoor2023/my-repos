<?php

use App\Enums\SalesManagement\SellingStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sale_advertisements', function (Blueprint $table) {
            $newValues = [
                SellingStatusEnum::NOT_YET_SOLD->value,
                SellingStatusEnum::QUOTATIONS->value,
                SellingStatusEnum::BOOKING->value,
                SellingStatusEnum::CONTRACT->value,
                SellingStatusEnum::LOAN_APPLICATION_STATUS->value,
                SellingStatusEnum::OWNERSHIP_TRANSFER->value,
                SellingStatusEnum::GET_PROMOTIONS->value,
            ];
            $values = implode("','", $newValues);
            DB::statement("ALTER TABLE sale_advertisements MODIFY selling_status ENUM('$values') NOT NULL");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $previousValues = [
            SellingStatusEnum::QUOTATIONS->value,
            SellingStatusEnum::BOOKING->value,
            SellingStatusEnum::CONTRACT->value,
            SellingStatusEnum::LOAN_APPLICATION_STATUS->value,
            SellingStatusEnum::OWNERSHIP_TRANSFER->value,
            SellingStatusEnum::GET_PROMOTIONS->value,
        ];
        if (! empty($previousValues)) {
            $values = implode("','", $previousValues);
            DB::statement("ALTER TABLE sale_advertisements MODIFY selling_status ENUM('$values') NOT NULL");
        }
    }
};
