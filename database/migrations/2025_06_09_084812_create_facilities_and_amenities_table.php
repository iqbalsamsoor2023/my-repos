<?php

use App\Enums\FacilityAndAmenity\AmenityTypeEnum;
use App\Enums\FacilityAndAmenity\FacilityAmenityTypeEnum;
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
        Schema::create('facilities_and_amenities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_in_thai');
            $table->enum('type', array_column(FacilityAmenityTypeEnum::cases(), 'value'));
            $table->enum('amenity_type', array_column(AmenityTypeEnum::cases(), 'value'))->nullable();
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
        Schema::dropIfExists('facilities_and_amenities');
    }
};
