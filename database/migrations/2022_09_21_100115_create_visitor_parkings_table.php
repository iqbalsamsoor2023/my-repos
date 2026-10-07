<?php

use App\Models\Calculation;
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
        Schema::create('visitor_parkings', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(VisitorLog::class)->constrained();
            $table->foreignIdFor(Calculation::class)->constrained();
            $table->float('discount_value')->default(0);
            $table->float('amount_to_pay')->default(0);
            $table->float('amount_paid')->default(0);
            $table->boolean('is_penalty')->default(0);
            $table->boolean('is_stamp')->default(0);
            $table->json('calculation_records');
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
        Schema::dropIfExists('visitor_parkings');
    }
};
