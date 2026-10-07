<?php

use App\Models\Feature;
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
        Schema::create('features', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_user_feature');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('residence_feature', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Residence::class);
            $table->foreignIdFor(Feature::class);
            $table->boolean('is_active');
            $table->timestamps();
        });

        Schema::create('residence_device_account', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Residence::class);
            $table->string('platform')->comment('sgoc, fm');
            $table->unsignedBigInteger('user_id');
            $table->boolean('is_active')->default(1);
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
        Schema::dropIfExists('features');
        Schema::dropIfExists('residence_feature');
        Schema::dropIfExists('residence_device_account');
    }
};
