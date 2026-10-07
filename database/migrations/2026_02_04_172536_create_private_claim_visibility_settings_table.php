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
        Schema::create('private_claim_visibility_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Residence::class)->constrained();
            $table->foreignIdFor(PrivateClaimItem::class)->constrained();
            $table->boolean('is_enabled')->default(1)->comment('show or hide item to residents');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('private_claim_visibility_settings');
    }
};