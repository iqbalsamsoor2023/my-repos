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
        Schema::create('facility_bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('facility_id');
            $table->foreignIdFor(User::class);
            $table->foreignIdFor(Unit::class)->nullable();
            $table->string('ref_no', 20)->unique()->nullable();
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->tinyInteger('status')->nullable()->comment('0-Pending, 1-Approved, 2-Reject, 3-Complete, 4-No Show');
            $table->foreignIdFor(User::class, 'created_by');
            $table->foreignIdFor(User::class, 'updated_by')->nullable();
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
        Schema::dropIfExists('facility_bookings');
    }
};
