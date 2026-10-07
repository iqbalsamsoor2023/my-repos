<?php

use App\Models\Amenity;
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
        Schema::create('warranty_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Unit::class);
            $table->foreignIdFor(User::class);
            $table->foreignIdFor(Amenity::class);
            $table->dateTime('stop_remind_at')->nullable()->comment('user tick to stop remind them');
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
        Schema::dropIfExists('warranty_reminders');
    }
};
