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
        Schema::table('visitor_sequences', function (Blueprint $table) {
            $table->index(['id', 'last_sequence'], 'idx_visitor_sequences_id_sequence');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visitor_sequences', function (Blueprint $table) {
            $table->dropIndex('idx_visitor_sequences_id_sequence');
        });
    }
};
