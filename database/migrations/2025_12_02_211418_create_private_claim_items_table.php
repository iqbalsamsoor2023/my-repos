<?php

use App\Models\PrivateClaimCategory;
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
        Schema::create('private_claim_items', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(PrivateClaimCategory::class)->nullable()->constrained();
            $table->string('name')->unique();
            $table->string('name_th')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('private_claim_items');
    }
};