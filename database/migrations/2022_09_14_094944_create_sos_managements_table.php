<?php

use App\Models\Unit;
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
        Schema::create('sos_managements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->foreign('created_by_id')->references('id')->on('users');
            $table->foreignIdFor(Unit::class)->constrained();
            $table->unsignedBigInteger('accepted_by_id')->nullable()->comment('id in users sgoc db');
            $table->integer('user_action_request')->nullable()->comment('1-call ambulance, 2-call police');
            $table->string('longitude')->nullable();
            $table->string('latitude')->nullable();
            $table->integer('status')->comment('1-pending, 2-in progress, 3-cancelled, 4-completed');
            $table->string('remark')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sos_managements');
    }
};
