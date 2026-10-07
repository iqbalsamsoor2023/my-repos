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
        Schema::table('unit_user', function (Blueprint $table) {
            $table->dropColumn('email_status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('unit_user', function (Blueprint $table) {
            $table->tinyInteger('email_status')->nullable()->after('approval_status')->comment('1-Verified, 2-Pending');
        });
    }
};
