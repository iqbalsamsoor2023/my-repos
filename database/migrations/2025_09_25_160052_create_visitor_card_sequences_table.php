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
        Schema::create('visitor_card_sequences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('residence_id');
            $table->unsignedInteger('last_sequence')->default(0);
            $table->timestamps();

            $table->unique('residence_id');
            $table->index('residence_id');
            $table->foreign('residence_id')->references('id')->on('residences')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visitor_card_sequences');
    }
};
