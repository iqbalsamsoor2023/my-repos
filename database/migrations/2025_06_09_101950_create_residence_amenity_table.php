<?php

use App\Models\FacilityAndAmenity;
use App\Models\Residence;
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
        Schema::create('residence_amenity', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Residence::class);
            $table->foreignIdFor(FacilityAndAmenity::class);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_claimable')->default(false);
            $table->boolean('is_bookable')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('residence_amenity');
    }
};
