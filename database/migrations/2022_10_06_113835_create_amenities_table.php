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
        Schema::create('amenities', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Residence::class)->constrained();
            $table->string('amenity_name');
            $table->tinyInteger('warranty_period')->default('0');
            $table->string('period_type')->nullable()->comment('month, year');
            $table->string('supplier')->nullable();
            $table->boolean('is_out_warranty')->default('0');
            $table->string('remark')->nullable();
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
        Schema::dropIfExists('amenities');
    }
};
