<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unit_user', function (Blueprint $table) {
            $table->index(['unit_id', 'deleted_at'], 'unit_user_unit_id_deleted_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('unit_user', function (Blueprint $table) {
            $table->dropIndex('unit_user_unit_id_deleted_at_index');
        });
    }
};
