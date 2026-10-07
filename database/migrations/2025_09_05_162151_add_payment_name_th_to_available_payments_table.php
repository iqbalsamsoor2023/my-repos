<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('available_payments', function (Blueprint $table) {
            $table->string('payment_name_th')->after('payment_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('available_payments', function (Blueprint $table) {
            $table->dropColumn('payment_name_th');
        });
    }
};
