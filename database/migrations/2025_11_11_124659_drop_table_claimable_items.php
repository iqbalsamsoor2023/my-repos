<?php

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
        Schema::dropIfExists('claimable_items');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('claimable_items', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Residence::class);
            $table->string('item');
            $table->timestamps();
            $table->softDeletes();
        });
    }
};
