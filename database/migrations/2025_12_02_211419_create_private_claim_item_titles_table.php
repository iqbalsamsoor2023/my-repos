<?php

use App\Models\PrivateClaimItem;
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
        Schema::create('private_claim_item_titles', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(PrivateClaimItem::class)->nullable()->constrained();
            $table->foreignId('private_claim_item_option_id')->constrained()->cascadeOnDelete();
            $table->string('option_name');
            $table->string('option_name_th')->nullable();  
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('private_claim_item_titles');
    }
};