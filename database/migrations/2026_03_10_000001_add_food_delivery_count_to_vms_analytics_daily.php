<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vms_analytics_daily', function (Blueprint $table) {
            $table->unsignedInteger('food_delivery_count')
                ->default(0)
                ->after('food_delivery_breakdown');
            $table->unsignedInteger('courier_count')
                ->default(0)
                ->after('food_delivery_count');
        });
    }

    public function down(): void
    {
        Schema::table('vms_analytics_daily', function (Blueprint $table) {
            $table->dropColumn('food_delivery_count');
            $table->dropColumn('courier_count');
        });
    }
};
