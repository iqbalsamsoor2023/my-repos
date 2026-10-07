<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pulse was moved onto its own `pulse` database connection. Laravel/pulse's
 * original migration is already recorded as run, so it will not fire again on
 * deploy — this migration does the relocation: drop the stale tables from the
 * application database and rebuild them on the dedicated connection.
 *
 * The schema is copied verbatim from laravel/pulse's own migration (frozen
 * since Pulse v1) rather than juggling the default connection at runtime.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['pulse_aggregates', 'pulse_entries', 'pulse_values'] as $table) {
            Schema::dropIfExists($table);
        }

        $connection = config('pulse.storage.database.connection');

        if ($connection === null || $connection === config('database.default')) {
            return;
        }

        $schema = Schema::connection($connection);

        if ($schema->hasTable('pulse_values')) {
            return;
        }

        $driver = DB::connection($connection)->getDriverName();

        $keyHash = function (Blueprint $table) use ($driver): void {
            match ($driver) {
                'mariadb', 'mysql' => $table->char('key_hash', 16)->charset('binary')->virtualAs('unhex(md5(`key`))'),
                'pgsql' => $table->uuid('key_hash')->storedAs('md5("key")::uuid'),
                default => $table->string('key_hash'),
            };
        };

        $schema->create('pulse_values', function (Blueprint $table) use ($keyHash): void {
            $table->id();
            $table->unsignedInteger('timestamp');
            $table->string('type');
            $table->mediumText('key');
            $keyHash($table);
            $table->mediumText('value');

            $table->index('timestamp');
            $table->index('type');
            $table->unique(['type', 'key_hash']);
        });

        $schema->create('pulse_entries', function (Blueprint $table) use ($keyHash): void {
            $table->id();
            $table->unsignedInteger('timestamp');
            $table->string('type');
            $table->mediumText('key');
            $keyHash($table);
            $table->bigInteger('value')->nullable();

            $table->index('timestamp');
            $table->index('type');
            $table->index('key_hash');
            $table->index(['timestamp', 'type', 'key_hash', 'value']);
        });

        $schema->create('pulse_aggregates', function (Blueprint $table) use ($keyHash): void {
            $table->id();
            $table->unsignedInteger('bucket');
            $table->unsignedMediumInteger('period');
            $table->string('type');
            $table->mediumText('key');
            $keyHash($table);
            $table->string('aggregate');
            $table->decimal('value', 20, 2);
            $table->unsignedInteger('count')->nullable();

            $table->unique(['bucket', 'period', 'type', 'aggregate', 'key_hash']);
            $table->index(['period', 'bucket']);
            $table->index('type');
            $table->index(['period', 'type', 'aggregate', 'bucket']);
        });
    }

    public function down(): void
    {
        $connection = config('pulse.storage.database.connection');

        if ($connection === null || $connection === config('database.default')) {
            return;
        }

        $schema = Schema::connection($connection);

        foreach (['pulse_aggregates', 'pulse_entries', 'pulse_values'] as $table) {
            $schema->dropIfExists($table);
        }
    }
};
