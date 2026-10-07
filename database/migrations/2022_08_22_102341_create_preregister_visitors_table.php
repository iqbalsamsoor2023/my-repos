<?php

use App\Models\Unit;
use App\Models\User;
use App\Models\Visitor;
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
        Schema::create('preregister_visitors', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Visitor::class)->constrained();
            $table->string('visitor_code');
            $table->unsignedInteger('arrival_type')->nullable()->comment('1-Drive In 2-Walk In');
            $table->unsignedInteger('vehicle_type')->nullable()->comment('1-car 2-truck 3-Motorbike 4-Van 5-Taxi 6-Pickup');
            $table->string('visitor_purpose')->nullable();
            $table->string('vehicle_plate_no')->nullable();
            $table->dateTime('validity_start_date');
            $table->dateTime('validity_end_date')->nullable()->comment('required for duration time visitor');
            $table->foreignIdFor(Unit::class)->nullable()->constrained();
            $table->foreignIdFor(User::class)->nullable()->constrained();
            $table->boolean('is_multiple_entry');
            $table->boolean('is_qr_code_expired')->default(0);
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
        Schema::dropIfExists('preregister_visitors');
    }
};
