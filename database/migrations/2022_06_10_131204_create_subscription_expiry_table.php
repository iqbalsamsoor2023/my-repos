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
        Schema::create('subscription_expires', function (Blueprint $table) {
            $table->id();
            $table->string('type')->comment('Residence, Sgoc');
            $table->foreignId('company_id')->nullable()->constrained('companies');
            $table->foreignId('residence_id')->nullable()->constrained('residences');
            $table->date('expiry_date');
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
        Schema::dropIfExists('subscription_expires');
    }
};
