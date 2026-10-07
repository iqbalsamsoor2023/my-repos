<?php

use App\Models\Erp\DigitalTool;
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
        Schema::table('residence_device_account', function (Blueprint $table) {
            $table->foreignIdFor(DigitalTool::class)->after('id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('residence_device_account', function (Blueprint $table) {
            $table->dropForeign('digital_tool_id_foreign');
            $table->dropColumn('digital_tool_id');
        });
    }
};
