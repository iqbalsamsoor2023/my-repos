<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('facilities');
        Schema::dropIfExists('facility_timeslots');
        Schema::dropIfExists('facility_bookings');
        Schema::dropIfExists('facility_residences');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
