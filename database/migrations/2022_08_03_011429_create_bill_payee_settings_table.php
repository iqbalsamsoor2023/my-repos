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
        Schema::create('bill_payee_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Residence::class)->constrained();
            $table->string('payee_name');
            $table->string('payee_name_th')->nullable();
            $table->string('payee_email')->nullable();
            $table->string('payee_phone_no')->nullable();
            $table->longText('payee_address')->nullable();
            $table->longText('payee_address_th')->nullable();
            $table->longText('remark')->nullable();
            $table->longText('remark_th')->nullable();
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
        Schema::dropIfExists('bill_payee_settings');
    }
};
