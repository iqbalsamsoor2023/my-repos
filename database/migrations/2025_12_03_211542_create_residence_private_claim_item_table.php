<?php

use App\Models\PrivateClaimItem;
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
        Schema::create('residence_private_claim_item', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Residence::class)->constrained();
            $table->foreignIdFor(PrivateClaimItem::class);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('residence_private_claim_item');
    }
};