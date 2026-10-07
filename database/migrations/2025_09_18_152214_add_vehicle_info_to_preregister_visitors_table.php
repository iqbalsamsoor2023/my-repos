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
        Schema::table('preregister_visitors', function (Blueprint $table) {
            $table->string('company_name')->nullable()->after('is_qr_code_expired');
            $table->integer('passenger_count')->nullable()->after('company_name');
            $table->string('remark')->nullable()->after('passenger_count');
            $table->json('vehicle_info')->nullable()->after('remark');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('preregister_visitors', function (Blueprint $table) {
            $table->dropColumn([
                'company_name',
                'passenger_count',
                'remark',
                'vehicle_info',
            ]);
        });
    }
};
