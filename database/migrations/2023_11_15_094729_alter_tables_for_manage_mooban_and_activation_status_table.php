<?php

use App\Models\ResidenceActivationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('units', function (Blueprint $table) {
            $table->tinyInteger('property_type')->nullable()->after('myseevr_link');
        });

        Schema::table('residences', function (Blueprint $table) {
            $table->renameColumn('type', 'pmoc_type');
            $table->boolean('is_active')->nullable()->comment('will be removed after manage mooban')->change();
            $table->boolean('is_demo')->nullable()->comment('will be removed after manage mooban')->change();
        });

        DB::statement('ALTER TABLE `residences` MODIFY `pmoc_type` TINYINT UNSIGNED NULL COMMENT "1-Art Gallery, 2-Community Mall, 3-Hospital, 4-Hotel, 5-Office Building, 6-Religious Organization, 7-School, 8-Shopping Mall, 9-Showroom, 10-Sport Club";');

        Schema::table('residences', function (Blueprint $table) {
            $table->foreignIdFor(ResidenceActivationStatus::class)->after('is_demo');
            $table->json('internet_provider_id')->nullable()->after('residence_activation_status_id');
            $table->integer('guard_house_entry_number')->nullable()->after('internet_provider_id');
            $table->tinyInteger('guard_house_lane_type')->nullable()->comment('1-Single Lane, 2-Dual Lane')->after('guard_house_entry_number');
        });

        Schema::create('residence_activation_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('status');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('units', function (Blueprint $table) {
            $table->dropColumn('property_type');
        });

        Schema::table('residences', function (Blueprint $table) {
            $table->dropColumn([
                'residence_activation_status_id',
                'internet_provider_id',
                'guard_house_entry_number',
                'guard_house_lane_type',
            ]);
        });

        Schema::table('residences', function (Blueprint $table) {
            $table->renameColumn('pmoc_type', 'type');
        });

        Schema::dropIfExists('residence_activation_statuses');
    }
};
