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
        Schema::table('visitor_logs', function (Blueprint $table) {
            // For incremental sync filtering
            $table->index('updated_at', 'idx_visitor_logs_updated_at');

            // For stable chunking with updated_at
            $table->index(['updated_at', 'id'], 'idx_visitor_logs_updated_at_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visitor_logs', function (Blueprint $table) {
            $table->dropIndex('idx_visitor_logs_updated_at');
            $table->dropIndex('idx_visitor_logs_updated_at_id');
        });
    }
};
