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
     * by improving audit lookups for residence activation status changes.
     */
    public function up(): void
    {
        Schema::table('audits', function (Blueprint $table) {
            // Composite index for filtering audits by residence type and date range
            // Used in: WHERE auditable_type = 'App\Models\Residence' AND auditable_id IN (...) AND created_at <= ?
            $table->index(['auditable_type', 'auditable_id', 'created_at'], 'idx_audits_type_id_created');

            // Index for auditable_id and created_at for ordering within residence
            // Used in: ORDER BY auditable_id, created_at DESC
            $table->index(['auditable_id', 'created_at'], 'idx_audits_id_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audits', function (Blueprint $table) {
            $table->dropIndex('idx_audits_type_id_created');
            $table->dropIndex('idx_audits_id_created');
        });
    }
};
