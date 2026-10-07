<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * These indexes optimize the Market Share Progress Widget queries
     * by improving residence filtering by mooban_type, sub_type, and status.
     */
    public function up(): void
    {
        Schema::table('residences', function (Blueprint $table) {
            // Composite index for filtering public residences by subtype
            // Used in: WHERE mooban_type = 'public' AND sub_type = ? AND created_at <= ?
            $table->index(['mooban_type', 'sub_type', 'created_at'], 'idx_residences_type_subtype_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('residences', function (Blueprint $table) {
            $table->dropIndex('idx_residences_type_subtype_created');
        });
    }
};
