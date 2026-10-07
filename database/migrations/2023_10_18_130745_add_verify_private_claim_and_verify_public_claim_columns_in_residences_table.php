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
        Schema::table('residences', function (Blueprint $table) {
            $table->boolean('verify_private_claim')->comment('show or hide verified button for private claim')->after('appointment_datetime_status')->default(0);
            $table->boolean('verify_public_claim')->comment('show or hide verified button for public claim')->after('verify_private_claim')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('residences', function (Blueprint $table) {
            $table->dropColumn('verify_private_claim');
            $table->dropColumn('verify_public_claim');
        });
    }
};
