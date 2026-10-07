<?php

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
        Schema::create('custom_rental_contracts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('unit_id')->index();
            $table->integer('contract_months');
            $table->bigInteger('rent_price');
            $table->bigInteger('deposit');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_rental_contracts');
    }
};
