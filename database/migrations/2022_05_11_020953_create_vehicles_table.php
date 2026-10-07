<?php

use App\Models\Unit;
use App\Models\User;
use App\Models\VehicleModel;
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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('province_id');
            $table->foreignIdFor(Unit::class)->constrained();
            $table->foreignIdFor(User::class)->constrained();
            $table->foreignIdFor(VehicleModel::class)->nullable()->constrained();
            $table->foreignId('insurance_company_id')->nullable()->constrained('companies');
            $table->string('plate_number');
            $table->string('generated_vehicle_no')->nullable()->unique();
            $table->year('purchase_year');
            $table->date('roadtax_expiry_date')->nullable();
            $table->date('insurance_expiry_date')->nullable();
            $table->string('policy_no')->nullable();
            $table->boolean('is_access_card');
            $table->boolean('is_car_sticker');
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
        Schema::dropIfExists('vehicles');
    }
};
