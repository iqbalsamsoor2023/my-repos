<?php

use App\Models\User;
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
        Schema::create('model_histories', function (Blueprint $table) {
            $table->increments('id');
            $table->foreignIdFor(User::class);
            $table->string('event');
            $table->morphs('modelable');
            $table->text('old_values')->nullable();
            $table->text('new_values')->nullable();
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
        Schema::dropIfExists('model_histories');
    }
};
