<?php

use App\Models\Invoice;
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
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Invoice::class)->constrained();
            $table->string('name');
            $table->integer('quantity')->default(1);
            $table->decimal('price', 19, 2);
            $table->decimal('vat', 19, 2)->default(0.00);
            $table->tinyInteger('status')->comment('1-Unpaid, 2-Paid, 3-Partially Paid, 4-Pending, 5-Failed, 6-Cancel')->default(1);
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
        Schema::dropIfExists('items');
    }
};
