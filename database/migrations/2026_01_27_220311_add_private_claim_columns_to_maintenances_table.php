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
        Schema::table('maintenances', function (Blueprint $table) {
            // $table->foreignId('private_claim_category_id')
            //     ->nullable()
            //     ->after('maintainable_claim_number')
            //     ->constrained()
            //     ->nullOnDelete();
                
            // $table->foreignId('private_claim_item_id')
            //     ->nullable()
            //     ->after('private_claim_category_id')
            //     ->constrained()
            //     ->nullOnDelete();

            // $table->foreignId('private_claim_item_title_id')
            //     ->nullable()
            //     ->after('private_claim_item_id')
            //     ->constrained()
            //     ->nullOnDelete();

            $table->json('private_claim_snapshot')->nullable()->after('private_claim_item_title_id');
            $table->string('other_private_claim_item')->nullable()->after('private_claim_item_title_id');
            $table->string('other_private_claim_category')->nullable()->after('other_private_claim_item');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('maintenances', function (Blueprint $table) {
            $table->dropForeign(['private_claim_category_id']);
            $table->dropForeign(['private_claim_item_id']);
            $table->dropForeign(['private_claim_item_title_id']);
            $table->dropColumn([
                'private_claim_category_id',
                'private_claim_item_id',
                'private_claim_item_title_id',
                'private_claim_snapshot',
                'other_private_claim_category',
                'other_private_claim_item',
            ]);
        });
    }
};