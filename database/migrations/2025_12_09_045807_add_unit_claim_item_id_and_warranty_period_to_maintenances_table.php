<?php

use App\Models\PrivateClaimItem;
use App\Models\PrivateClaimItemTitle;
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
        Schema::table('maintenances', function (Blueprint $table) {
            $table->foreignIdFor(PrivateClaimItem::class)->nullable()->constrained()->after('private_claim_category_id');
            $table->foreignIdFor(PrivateClaimItemTitle::class)->nullable()->constrained()->after('private_claim_item_id');
            $table->integer('warranty_period')->comment('in month(s)')->nullable()->after('private_claim_item_title_id');;
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('maintenances', function (Blueprint $table) {
            $table->dropColumn(['private_claim_item_id', 'warranty_period', 'private_claim_item__title_id']);
        });
    }
};