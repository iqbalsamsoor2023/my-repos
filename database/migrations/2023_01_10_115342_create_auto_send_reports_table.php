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
        Schema::create('auto_send_reports', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->foreignIdFor(Residence::class);
            $table->string('time')->default('00:00');
            $table->integer('hour')->default('24');
            $table->string('module_type');
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
        Schema::dropIfExists('auto_send_reports');
    }
};
