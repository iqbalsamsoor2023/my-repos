<?php

use App\Enums\SalesManagement\ConstructionProgressEnum;
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
            $table->string('sample_room_vr_link')->nullable()->after('myseevr_link');
            $table->enum('construction_progress', array_column(ConstructionProgressEnum::cases(), 'value'))->nullable()->after('sample_room_vr_link');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->dropColumn([
                'sample_room_vr_link',
                'construction_progress',
            ]);
        });
    }
};
