<?php

use App\Models\PrivateClaimItem;
use App\Models\Residence;
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
        Schema::create('private_claim_item_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Residence::class)->constrained();
            $table->foreignIdFor(PrivateClaimItem::class)->constrained();
            $table->json('private_claim_item_details');
            $table->tinyInteger('warranty_period')->nullable()->comment('(in month');
            $table->string('supplier_company_name')->nullable();
            $table->string('supplier_company_name_th')->nullable();
            $table->string('supplier_item_brand')->nullable();
            $table->string('pic_name')->nullable();
            $table->string('pic_mobile_no')->nullable();
            $table->string('pic_email')->nullable();
            $table->boolean('is_out_warranty')->default('0');
            $table->string('remark')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('residence_private_claim_item_warranties');
    }
};