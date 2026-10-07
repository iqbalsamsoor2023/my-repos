<?php

use App\Models\Residence;
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
        Schema::create('committees', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Unit::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Residence::class)->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('role');
            $table->date('term_start');
            $table->date('term_end')->nullable();
            $table->timestamps();

            // Indexes for performance optimization
            $table->index(['residence_id', 'role', 'term_end'], 'idx_residence_role_term');
            $table->index(['user_id', 'residence_id'], 'idx_user_residence');
            $table->index(['term_start', 'term_end'], 'idx_term_dates');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('committees');
    }
};
