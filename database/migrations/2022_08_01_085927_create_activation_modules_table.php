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
        Schema::create('activation_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Residence::class)->constrained();
            $table->tinyInteger('module_type')->comment('
            1 - inbox
            2 - visitor
            3 - claim
            4 - parcel
            5 - booking
            6 - developer
            7 - contact
            8 - billing
            9 - brand
            10 - purpose of visit
            11 - contact number
            12 - temperature
            13 - company name
            14 - passenger
            15 - remark
            16 - pdpa
            17 - color
            18 - visitor photo
            ');
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
        Schema::dropIfExists('activation_modules');
    }
};
