<?php

use App\Models\Residence;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('warranty_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Residence::class)->constrained();
            $table->boolean('has_other_option');
            $table->string('remark')->nullable();
            $table->boolean('has_warranty_reminder')->default(0)->comment('show or hide warranty reminder pop out to residents');
            $table->tinyInteger('reminder_day')->unsigned()->nullable()->comment('remind how many days before warranty expired');
            $table->boolean('has_appointment_schedule')->default(0);
            $table->boolean('has_verification')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warranty_settings');
    }
};