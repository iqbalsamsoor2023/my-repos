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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_no', 30);
            $table->foreignIdFor(BillPayeeSetting::class)->constrained();
            $table->foreignId('payer_unit_id')->constrained('units');
            $table->string('bill_no')->nullable();
            $table->date('bill_date')->nullable();
            $table->date('due_date');
            $table->tinyInteger('status')->comment('1-Unpaid, 2-Paid, 3-Partially Paid, 4-Pending, 5-Failed, 6-Cancel');
            $table->decimal('total_amount', 19, 2)->nullable();
            $table->decimal('amount_due', 19, 2)->nullable();
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
        Schema::dropIfExists('invoices');
    }
};
