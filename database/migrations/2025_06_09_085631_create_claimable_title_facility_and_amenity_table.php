<?php

use App\Models\ClaimableTitle;
use App\Models\FacilityAndAmenity;
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
        Schema::create('claimable_title_facility_and_amenity', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(ClaimableTitle::class);
            $table->foreignIdFor(FacilityAndAmenity::class);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('claimable_title_facility_and_amenity');
    }
};
