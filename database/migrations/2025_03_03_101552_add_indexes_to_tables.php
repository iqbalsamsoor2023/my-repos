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
        // Residence Table
        Schema::table('residences', function (Blueprint $table) {
            $table->index('mooban_type', 'idx_mooban_type');
            $table->index('sub_type', 'idx_sub_type');
            $table->index('residence_activation_status_id', 'idx_residence_activation_status');
            $table->index('created_at', 'idx_created_at');
            $table->index('updated_at', 'idx_updated_at');

            // Fulltext index for searching main_road
            $table->fullText('main_road', 'idx_main_road');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback indexes
        Schema::table('residence', function (Blueprint $table) {
            $table->dropIndex('idx_mooban_type');
            $table->dropIndex('idx_sub_type');
            $table->dropIndex('idx_residence_activation_status');
            $table->dropIndex('idx_created_at');
            $table->dropIndex('idx_updated_at');
            $table->dropFullText('idx_main_road');
        });
    }
};
