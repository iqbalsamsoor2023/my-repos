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
        // ---------------------------------------------------------
        // blacklisted_visitors
        // ---------------------------------------------------------
        if (! $this->hasIndex(
            'blacklisted_visitors',
            'idx_blacklisted_visitors_visitor_id'
        )) {
            Schema::table('blacklisted_visitors', function (Blueprint $table) {
                    $table->index('visitor_id', 'idx_blacklisted_visitors_visitor_id');
                }
            );
        }

        // ---------------------------------------------------------
        // preregister_visitors
        // ---------------------------------------------------------
        if (! $this->hasIndex(
            'preregister_visitors',
            'idx_preregister_visitors_visitor_id'
        )) {
            Schema::table('preregister_visitors', function (Blueprint $table) {
                    $table->index('visitor_id', 'idx_preregister_visitors_visitor_id');
                }
            );
        }

        // ---------------------------------------------------------
        // visitor_logs
        // Large table (~4.5M rows)
        // ---------------------------------------------------------
        if (! $this->hasIndex(
            'visitor_logs',
            'idx_visitor_logs_visitor_id'
        )) {
            Schema::table('visitor_logs', function (Blueprint $table) {
                    $table->index('visitor_id', 'idx_visitor_logs_visitor_id');
                }
            );
        }

        // ---------------------------------------------------------
        // visitor_logs_archive
        // Large table (~7.5M rows)
        // ---------------------------------------------------------
        if (! $this->hasIndex(
            'visitor_logs_archive',
            'idx_visitor_logs_archive_visitor_id'
        )) {
            Schema::table('visitor_logs_archive', function (Blueprint $table) {
                    $table->index('visitor_id', 'idx_visitor_logs_archive_visitor_id');
                }
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // ---------------------------------------------------------
        // visitor_logs_archive
        // ---------------------------------------------------------
        if ($this->hasIndex(
            'visitor_logs_archive',
            'idx_visitor_logs_archive_visitor_id'
        )) {
            Schema::table('visitor_logs_archive', function (Blueprint $table) {
                    $table->dropIndex('idx_visitor_logs_archive_visitor_id');
                }
            );
        }

        // ---------------------------------------------------------
        // visitor_logs
        // ---------------------------------------------------------
        if ($this->hasIndex(
            'visitor_logs',
            'idx_visitor_logs_visitor_id'
        )) {
            Schema::table('visitor_logs', function (Blueprint $table) {
                    $table->dropIndex('idx_visitor_logs_visitor_id');
                }
            );
        }

        // ---------------------------------------------------------
        // preregister_visitors
        // ---------------------------------------------------------
        if ($this->hasIndex(
            'preregister_visitors',
            'idx_preregister_visitors_visitor_id'
        )) {
            Schema::table('preregister_visitors', function (Blueprint $table) {
                    $table->dropIndex('idx_preregister_visitors_visitor_id');
                }
            );
        }

        // ---------------------------------------------------------
        // blacklisted_visitors
        // ---------------------------------------------------------
        if ($this->hasIndex(
            'blacklisted_visitors',
            'idx_blacklisted_visitors_visitor_id'
        )) {
            Schema::table('blacklisted_visitors', function (Blueprint $table) {
                    $table->dropIndex('idx_blacklisted_visitors_visitor_id');
                }
            );
        }
    }

    /**
     * Check whether an index exists.
     */
    private function hasIndex(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();

        $database = $connection
            ->getDatabaseName();

        $result = $connection->select(
            '
                SELECT 1
                FROM information_schema.statistics
                WHERE table_schema = ?
                AND table_name = ?
                AND index_name = ?
                LIMIT 1
            ',
            [
                $database,
                $table,
                $indexName,
            ]
        );

        return ! empty($result);
    }
};
