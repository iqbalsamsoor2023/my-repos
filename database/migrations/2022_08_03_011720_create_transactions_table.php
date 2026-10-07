<?php

use App\Models\BillPayeeBankDetail;
use App\Models\Invoice;
use App\Models\Payment;
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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('ref_no')->nullable();
            $table->foreignIdFor(Invoice::class)->constrained();
            $table->foreignIdFor(BillPayeeBankDetail::class)->nullable()->constrained();
            $table->foreignIdFor(Payment::class)->constrained();
            $table->decimal('paid_amount', 19, 2);
            $table->dateTime('transaction_datetime')->nullable();
            $table->tinyInteger('status')->default('1')->comment('1-Pending, 2-Accepted, 3-Rejected');
            $table->string('payer_name')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->longText('remark')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transactions');
    }
};
