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
        Schema::dropIfExists('vms_visitor_logs_report');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot recreate the table as it's complex
        // If you need to rollback, restore from backup
    }
};
