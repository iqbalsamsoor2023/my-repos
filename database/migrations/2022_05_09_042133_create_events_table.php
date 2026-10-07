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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Residence::class)->constrained();
            $table->string('title');
            $table->longText('description');
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->boolean('is_active');
            $table->boolean('is_cancel')->default(0);
            $table->foreignId('created_by')->constrained('users')->comment('id in users');
            $table->foreignId('updated_by')->nullable()->constrained('users')->comment('id in users');
            $table->timestamps();
            $table->softDeletes();

            $table->index('residence_id');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('events');
    }
};
