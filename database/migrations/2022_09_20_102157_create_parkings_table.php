<?php

use App\Models\Residence;
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
        Schema::create('parkings', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Residence::class)->constrained();
            $table->tinyInteger('type')->comment('1 - free, 2 - paid');
            $table->tinyInteger('rate_mode')->comment('1 - per hour, 2 - per day')->default(0);
            $table->boolean('is_discount_coupon')->default(0);
            $table->tinyInteger('discount_type')->comment('1 - price, 2 - time')->default(0);
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
        Schema::dropIfExists('parkings');
    }
};
