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
        Schema::table('units', function (Blueprint $table) {
            $table->renameColumn('property_type', 'sub_type');
        });

        Schema::table('units', function (Blueprint $table) {
            $table->integer('sub_type')->change();
        });

        Schema::table('residences', function (Blueprint $table) {
            $table->smallInteger('sub_type')->nullable()->comment('Default Sub Type in Residence')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->renameColumn('sub_type', 'property_type');
        });
    }
};
