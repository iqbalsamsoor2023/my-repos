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
        Schema::table('visitor_logs', function (Blueprint $table) {
            $table->index(['arrival_type', 'deleted_at', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('visitor_logs', function (Blueprint $table) {
            $table->dropIndex(['arrival_type', 'deleted_at', 'created_at']);
        });
    }
};
