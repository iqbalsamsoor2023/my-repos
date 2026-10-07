<?php

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
        Schema::create('maintenances', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('maintainable_id');
            $table->string('maintainable_type');
            $table->string('maintainable_claim_number')->nullable();
            $table->json('amenity')->nullable();
            $table->longText('issue_description')->nullable();
            $table->dateTime('appointment_datetime')->nullable();
            $table->tinyInteger('status')->default('0')->comment('0-pending, 1-in progress, 2-completed');
            $table->boolean('is_verified')->nullable();
            $table->string('rating')->nullable();
            $table->longText('completed_remark')->nullable();
            $table->longText('verification_description')->nullable();
            $table->foreignId('reported_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index('maintainable_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('maintenances');
    }
};
