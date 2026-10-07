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
        Schema::table('visitor_cards', function (Blueprint $table) {
            $table->boolean('is_custom')->nullable()->default(0)->after('visitor_card_no');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('visitor_cards', function (Blueprint $table) {
            $table->dropColumn('is_custom');
        });
    }
};
