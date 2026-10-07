<?php

use App\Models\Residence;
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
        Schema::create('blacklisted_visitors', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Visitor::class)->constrained();
            $table->foreignIdFor(Residence::class)->constrained();
            $table->string('blacklist_remark')->nullable();
            $table->string('vehicle_plate_no')->nullable();
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
        Schema::dropIfExists('blacklisted_visitors');
    }
};
