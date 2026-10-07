<?php

use App\Models\Erp\DigitalTool;
use App\Models\Erp\DigitalToolSkuInf;
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
        Schema::table('residence_device_account', function (Blueprint $table) {
            $table->dropColumn('digital_tool_id');
            $table->foreignIdFor(DigitalToolSkuInf::class)->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('residence_device_account', function (Blueprint $table) {
            $table->foreignIdFor(DigitalTool::class)->after('id');
            $table->dropForeign('digital_tool_sku_inf_id_foreign');
            $table->dropColumn('digital_tool_sku_inf_id');
        });
    }
};
