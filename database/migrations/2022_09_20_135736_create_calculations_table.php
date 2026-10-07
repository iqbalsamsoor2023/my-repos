<?php

use App\Models\Parking;
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
        Schema::create('calculations', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Parking::class)->constrained();
            $table->tinyInteger('vehicle_type')->comment('1 - car, 2 - motorcycle');
            $table->boolean('is_stamp');
            $table->integer('free_parking_minutes')->default(0);
            $table->float('rate_per_hour')->default(0);
            $table->integer('chartered_duration')->default(0); // check balik
            $table->float('chartered_price')->default(0);
            $table->float('penalty')->default(0);
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
        Schema::dropIfExists('calculations');
    }
};
