<?php

use App\Models\Residence;
use App\Models\Unit;
use App\Models\User;
use App\Models\VisitorLog;
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
        Schema::create('visiting_arrangements', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(VisitorLog::class)->constrained();
            $table->foreignIdFor(Residence::class)->constrained();
            $table->foreignIdFor(Unit::class)->nullable()->constrained();
            $table->foreignIdFor(User::class)->nullable()->constrained();
            $table->unsignedBigInteger('estamp_by')->nullable();
            $table->unsignedInteger('estamp_by_type')->nullable()->comment('1-resident 2-pm 3-sg 4-sgoc');
            $table->unsignedInteger('status')->nullable()->comment('1-my visitor 2-not my visitor 3-cancel by sg 4- blacklisted');
            $table->string('feedback_remark')->nullable();
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
        Schema::dropIfExists('visiting_arrangements');
    }
};
