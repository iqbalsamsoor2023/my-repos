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
        Schema::table('visitor_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('courier_logistic_partner_id')->nullable()->after('visitor_purpose');
            $table->unsignedBigInteger('food_delivery_logistic_partner_id')->nullable()->after('courier_logistic_partner_id');

            $table->foreign('courier_logistic_partner_id')
                ->references('id')
                ->on('logistic_partners')
                ->onDelete('set null');

            $table->foreign('food_delivery_logistic_partner_id')
                ->references('id')
                ->on('logistic_partners')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visitor_logs', function (Blueprint $table) {
            $table->dropForeign(['courier_logistic_partner_id']);
            $table->dropForeign(['food_delivery_logistic_partner_id']);
            $table->dropColumn(['courier_logistic_partner_id', 'food_delivery_logistic_partner_id']);
        });
    }
};
