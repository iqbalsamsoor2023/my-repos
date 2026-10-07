<?php

use App\Models\BillPayeeSetting;
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
        Schema::create('bill_payee_bank_details', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(BillPayeeSetting::class)->constrained();
            $table->foreignId('bank_id');
            $table->string('payee_account_number', 20);
            $table->string('payee_account_name');
            $table->string('payee_account_name_th')->nullable();
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
        Schema::dropIfExists('bill_payee_bank_details');
    }
};
