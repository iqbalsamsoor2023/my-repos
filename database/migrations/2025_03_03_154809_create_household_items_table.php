<?php

use App\Enums\HouseholdItem\ItemCategoryEnum;
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
        Schema::create('household_items', function (Blueprint $table) {
            $table->id();
            $table->enum('category', array_column(ItemCategoryEnum::cases(), 'value'));
            $table->string('name');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('household_items');
    }
};
