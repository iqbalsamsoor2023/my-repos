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
        Schema::create('visitor_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Residence::class)->cascadeOnDelete();
            $table->date('log_date');
            $table->unsignedInteger('last_sequence')->default(0);
            $table->timestamps();

            $table->unique(['residence_id', 'log_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visitor_sequences');
    }
};
