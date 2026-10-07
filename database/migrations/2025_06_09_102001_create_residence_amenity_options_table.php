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
        Schema::create('residence_amenity_options', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('residence_amenity_id');
            $table->foreign('residence_amenity_id')->references('id')->on('residence_amenity')->onDelete('cascade');
            $table->string('name');
            $table->string('name_in_thai');
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
        Schema::dropIfExists('residence_amenity_options');
    }
};
