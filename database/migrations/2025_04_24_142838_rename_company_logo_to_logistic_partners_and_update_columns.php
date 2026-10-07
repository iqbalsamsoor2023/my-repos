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
        Schema::rename('companies_logo', 'logistic_partners');

        // Modify columns
        Schema::table('logistic_partners', function (Blueprint $table) {
            $table->smallInteger('category')->change();
            $table->smallInteger('modes')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert column changes
        Schema::table('logistic_partners', function (Blueprint $table) {
            $table->string('category')->change();
            $table->string('modes')->change();
        });

        // Rename table back
        Schema::rename('logistic_partners', 'companies_logo');
    }
};
