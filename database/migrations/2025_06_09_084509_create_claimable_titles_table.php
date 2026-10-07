<?php

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
        Schema::create('claimable_titles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_in_thai');
            $table->enum('category', [FacilityAmenityTypeEnum::AMENITY->value, FacilityAmenityTypeEnum::FACILITY->value]);
            $table->smallInteger('type')->nullable(); // 1 = Indoor, 2 = Outdoor, Null == Facility
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('claimable_titles');
    }
};
