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
        Schema::create('amenity_rates', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('amenity_rateable_id');
            $table->string('amenity_rateable_type');
            $table->decimal('price_per_hour', 10, 2)->default(0.0);
            $table->decimal('price_per_day', 10, 2)->default(0.0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('amenity_rates');
    }
};
