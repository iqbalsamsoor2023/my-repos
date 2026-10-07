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
            $table->string('residence_id')->after('visitor_id')->nullable();
            $table->index('residence_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visitor_logs', function (Blueprint $table) {
            $table->dropColumn('residence_id');
        });
    }
};
