<?php

use App\Models\Visitor;
use App\Models\VisitorCard;
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
        Schema::create('visitor_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Visitor::class)->constrained();
            $table->foreignIdFor(VisitorCard::class)->nullable()->constrained();
            $table->string('visitor_purpose')->nullable();
            $table->string('visitor_code');
            $table->string('visitor_generated_no');
            $table->string('company_name')->nullable();
            $table->unsignedInteger('arrival_type')->nullable()->comment('1-Drive In 2-Walk In');
            $table->unsignedInteger('vehicle_type')->nullable()->comment('1-car 2-truck 3-Motorbike 4-Van 5-Taxi 6-Pickup');
            $table->string('vehicle_plate_no')->nullable();
            $table->timestamp('arrival_time')->nullable();
            $table->timestamp('leave_time')->nullable();
            $table->string('temperature')->nullable();
            $table->integer('passenger_count')->nullable();
            $table->string('remark')->nullable();
            $table->string('blacklist_remark')->nullable()->comment('free text - entry, No Entry! - non entry visitor');
            $table->boolean('is_allowed')->nullable()->comment('0-no entry 1-entry null-not a blacklisted visitor');
            $table->boolean('is_pre_register')->default(0);
            $table->json('vehicle_info')->nullable()->comment('iApp LPR response');
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
        Schema::dropIfExists('visitor_logs');
    }
};
