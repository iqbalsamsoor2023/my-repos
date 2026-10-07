<?php

use App\Models\PrivateClaimItemTitle;
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
        Schema::create('private_claim_suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(PrivateClaimItemTitle::class)->constrained();
            $table->foreignIdFor(Residence::class)->constrained();
            $table->string('company_name');
            $table->string('company_name_th')->nullable();
            $table->string('brand')->nullable();
            $table->string('contact_person_name')->nullable();
            $table->string('contact_person_mobile_no')->nullable();
            $table->string('contact_person_email')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('private_claim_supplier');
    }
};