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
        Schema::table('app_versions', function (Blueprint $table) {
            $table->unsignedBigInteger('application_id')->after('id')->nullable();
            $table->boolean('force_update')->default(false)->after('application_version');
            $table->string('release_notes')->nullable()->after('force_update');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_versions', function (Blueprint $table) {
            $table->dropColumn(['application_id', 'force_update', 'release_notes']);
        });
    }
};
