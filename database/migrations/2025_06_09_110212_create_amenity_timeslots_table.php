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
        Schema::create('amenity_timeslots', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('amenity_timeslotable_id');
            $table->string('amenity_timeslotable_type');
            $table->tinyInteger('quota')->default(1);
            $table->tinyInteger('day');
            $table->time('start_at');
            $table->time('end_at');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('amenity_timeslots');
    }
};
