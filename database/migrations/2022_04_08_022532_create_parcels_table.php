<?php

use App\Models\Unit;
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
        Schema::create('parcels', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Unit::class)->constrained();
            $table->foreignId('courier_id')->nullable()->constrained('companies_logo')->comment('id in company logo');
            $table->foreignId('receiver_id')->nullable()->constrained('users');
            $table->string('receiver_name', 255)->nullable();
            $table->string('pickup_person_contact_no')->nullable();
            $table->string('pickup_person_name', 255)->nullable();
            $table->string('qr_code')->nullable();
            $table->string('parcel_generated_no', 16)->nullable()->unique();
            $table->string('tracking_no', 50)->nullable();
            $table->longText('description', 2000)->nullable();
            $table->smallInteger('status')->default(0)->comment('0-Pending Pickup, 1-Picked Up, 2-Not my parcel');
            $table->enum('pickup_type', [1, 2])->nullable()->comment('1-Recipient, 2-On-behalf');
            $table->dateTime('pickup_time')->nullable();
            $table->foreignId('created_by')->constrained('users')->comment('id in users');
            $table->foreignId('updated_by')->nullable()->constrained('users')->comment('id in users');
            $table->timestamps();
            $table->softDeletes();

            $table->index('receiver_id');
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
        Schema::dropIfExists('parcels');
    }
};
