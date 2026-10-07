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
        Schema::table('visitor_purposes', function (Blueprint $table) {
            $table->dropColumn('purpose_th');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('visitor_purposes', function (Blueprint $table) {
            $table->string('purpose_th')->nullable()->after('purpose');
        });
    }
};
