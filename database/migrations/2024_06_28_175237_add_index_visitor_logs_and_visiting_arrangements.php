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
        Schema::table('visiting_arrangements', function (Blueprint $table) {
            $table->index(['visitor_log_id', 'residence_id', 'deleted_at'], 'idx_va_log_res_del');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('visiting_arrangements', function (Blueprint $table) {
            $table->dropIndex(['visitor_log_id', 'residence_id', 'deleted_at']);
        });
    }
};
