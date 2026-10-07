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
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Residence::class);
            $table->string('invitation_code_owner')->unique()->nullable();
            $table->string('invitation_code_tenant')->unique()->nullable();
            $table->string('home_id')->unique()->nullable();
            $table->string('unit_size')->nullable()->comment('in sqm');
            $table->string('myseevr_link')->nullable();
            $table->string('unit_number');
            $table->string('street');
            $table->string('floor')->nullable();
            $table->string('block')->nullable();
            $table->smallInteger('status')->nullable()->comment('1. Occupied, 2. Abandoned, 3. Not Yet Sold');
            $table->date('move_in_at')->nullable();
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
        Schema::dropIfExists('units');
    }
};
