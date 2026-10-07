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
        Schema::table('user_tutorials', function (Blueprint $table) {
            $table->dropColumn('file_name');
            $table->string('platform')->after('id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_tutorials', function (Blueprint $table) {
            $table->string('file_name')->nullable()->after('type');
            $table->dropColumn('platform');
        });
    }
};
