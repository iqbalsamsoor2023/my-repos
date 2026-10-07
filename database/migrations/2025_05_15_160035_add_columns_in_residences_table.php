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
        Schema::table('residences', function (Blueprint $table) {
            $table->boolean('has_roof')
                ->nullable()
                ->after('verify_public_claim')
                ->comment('Indicates if the residence has a roof');

            $table->unsignedTinyInteger('entrance_barrier_type')
                ->nullable()
                ->after('has_roof')
                ->comment('0 = none, 1 = gate, 2 = barrier arm, 3 = other');

            $table->boolean('has_cctv')
                ->nullable()
                ->after('entrance_barrier_type')
                ->comment('Indicates if the residence has a cctv');

            $table->unsignedSmallInteger('cctv_count')
                ->default(0)
                ->after('has_cctv')
                ->comment('Number of CCTV cameras installed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('residences', function (Blueprint $table) {
            $table->dropColumn([
                'has_roof',
                'entrance_barrier_type',
                'has_cctv',
                'cctv_count',
            ]);
        });
    }
};
