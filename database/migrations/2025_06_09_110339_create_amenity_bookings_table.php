<?php

use App\Models\Unit;
use App\Models\User;
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
        Schema::create('amenity_bookings', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('amenity_bookable_id');
            $table->string('amenity_bookable_type');
            $table->foreignIdFor(User::class);
            $table->foreignIdFor(Unit::class);
            $table->string('ref_no', 20)->unique()->nullable();
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->tinyInteger('status')->nullable()->comment('0-Pending, 1-Approved, 2-Reject, 3-Complete, 4-No Show');
            $table->foreignIdFor(User::class, 'created_by');
            $table->foreignIdFor(User::class, 'updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('amenity_bookings');
    }
};
