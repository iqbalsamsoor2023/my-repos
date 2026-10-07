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
        Schema::table('other_amenities', function (Blueprint $table) {
            $table->boolean('is_show_warranty_reminder')->default(0)->comment('show or hide warranty reminder pop out to residents')->after('remark');
            $table->tinyInteger('remind_day')->unsigned()->nullable()->comment('remind how many days before warranty expired')->after('is_show_warranty_reminder');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('other_amenities', function (Blueprint $table) {
            $table->dropColumn('is_show_warranty_reminder');
            $table->dropColumn('remind_day');
        });
    }
};
