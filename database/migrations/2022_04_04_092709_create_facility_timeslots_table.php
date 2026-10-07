<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
        Schema::create('facility_timeslots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('facility_id');
            $table->tinyInteger('day');
            $table->time('start_at');
            $table->time('end_at');
            $table->boolean('is_active');
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
        Schema::dropIfExists('facility_timeslots');
    }
};
