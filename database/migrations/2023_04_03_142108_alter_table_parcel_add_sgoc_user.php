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
        Schema::table('parcels', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by')->nullable()->change();
        });

        Schema::table('parcels', function (Blueprint $table) {
            $table->renameColumn('created_by', 'created_by_mmb_user_id');
            $table->renameColumn('updated_by', 'updated_by_mmb_user_id');
        });

        Schema::table('parcels', function (Blueprint $table) {
            $db = DB::connection('sgoc')->getDatabaseName();

            $table->unsignedBigInteger('created_by_sgoc_user_id')->after('updated_by_mmb_user_id')->nullable();
            $table->foreign('created_by_sgoc_user_id')->references('id')->on($db.'.users');
            $table->unsignedBigInteger('updated_by_sgoc_user_id')->after('created_by_sgoc_user_id')->nullable();
            $table->foreign('updated_by_sgoc_user_id')->references('id')->on($db.'.users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('parcels', function (Blueprint $table) {
            $table->integer('created_by')->change();
            $table->dropColumn(['created_by_mmb_user_id', 'updated_by_mmb_user_id']);
            $table->dropColumn(['created_by_sgoc_user_id', 'updated_by_sgoc_user_id']);
        });
    }
};
