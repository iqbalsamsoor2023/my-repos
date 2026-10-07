<?php

use App\Models\Unit;
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
        Schema::create('pets', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Unit::class)->constrained();
            $table->foreignIdFor(User::class)->constrained();
            $table->string('breed');
            $table->tinyInteger('type')->comment('1.cat, 2.dog');
            $table->year('year');
            $table->string('generated_pet_no', 18)->nullable()->unique();
            $table->foreignId('created_by')->constrained('users')->comment('id in users');
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
        Schema::dropIfExists('pets');
    }
};
