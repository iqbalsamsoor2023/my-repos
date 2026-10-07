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
            $table->dropColumn('pmoc_type');
            $table->smallInteger('sub_type')->default(0)->after('mooban_type');
            $table->json('location_tags')->after('sub_type')->nullable();
            $table->longText('full_address')->after('location_tags');
            $table->longText('google_location_link')->after('full_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('residences', function (Blueprint $table) {
            $table->dropColumn([
                'google_location_link',
                'full_address',
                'location_tags',
                'sub_type',
            ]);
            $table->smallInteger('pmoc_type')->after('mooban_type');
        });
    }
};
