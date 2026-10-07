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
            $table->foreignId('sales_management_user_id')->nullable()->after('company_id')->constrained('users');
            $table->foreignId('rtm_user_id')->nullable()->after('sales_management_user_id')->constrained('users');
        });

        Schema::table('facilities', function (Blueprint $table) {
            $table->string('name_th')->after('name');
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
            $table->dropColumn([
                'sales_management_user_id',
                'rtm_user_id',
            ]);
        });

        Schema::table('facilities', function (Blueprint $table) {
            $table->dropColumn('name_th');
        });
    }
};
