<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('widget_aggregates', function (Blueprint $table) {
            $table->string('module', 50)->default('residence')->after('id');
        });

        // Drop old unique on 'key' alone, add composite unique on module+key
        Schema::table('widget_aggregates', function (Blueprint $table) {
            $table->dropUnique('widget_aggregates_key_unique');
            $table->unique(['module', 'key'], 'widget_aggregates_module_key_unique');
            $table->index('module', 'idx_wa_module');
        });

        // Set existing rows to 'residence' module
        DB::table('widget_aggregates')->whereNull('module')->orWhere('module', '')->update(['module' => 'residence']);
    }

    public function down(): void
    {
        Schema::table('widget_aggregates', function (Blueprint $table) {
            $table->dropIndex('idx_wa_module');
            $table->dropUnique('widget_aggregates_module_key_unique');
            $table->unique('key', 'widget_aggregates_key_unique');
            $table->dropColumn('module');
        });
    }
};
