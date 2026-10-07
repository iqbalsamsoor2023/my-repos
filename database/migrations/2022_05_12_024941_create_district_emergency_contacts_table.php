<?php

use App\Models\EmergencyContact;
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
        Schema::create('district_emergency_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(EmergencyContact::class)->constrained();
            $table->integer('thailand_district_id')->unsigned();
            $table->foreign('thailand_district_id')->references('id')->on('thailand_districts');
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
        Schema::dropIfExists('district_emergency_contacts');
    }
};
