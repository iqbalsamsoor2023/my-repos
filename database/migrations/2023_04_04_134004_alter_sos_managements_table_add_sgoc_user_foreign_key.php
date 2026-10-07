<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        Schema::table('sos_managements', function (Blueprint $table) {
            $db = DB::connection('sgoc')->getDatabaseName();

            $table->unsignedBigInteger('accepted_by_id')->references('id')->on($db.'.users')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sos_managements', function (Blueprint $table) {
            $table->dropForeign(['accepted_by_id']);
        });
    }
};
