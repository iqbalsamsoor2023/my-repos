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
        Schema::create('unit_household_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('unit_id')->index();
            $table->unsignedBigInteger('household_item_id')->index();
            $table->integer('quantity');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unit_household_items');
    }
};
