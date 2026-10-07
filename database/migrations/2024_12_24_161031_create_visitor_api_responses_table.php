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
        Schema::create('visitor_api_responses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('visitor_log_id');
            $table->dateTime('arrival_time')->nullable();
            $table->dateTime('leave_time')->nullable();
            $table->timestamps();
            $table->index(['visitor_log_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visitor_api_responses');
    }
};
