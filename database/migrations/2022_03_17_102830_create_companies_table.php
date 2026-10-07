<?php

use App\Models\User;
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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->integer('province_id')->nullable()->comment('id in thailand_provinces');
            $table->foreignIdFor(User::class)->nullable();
            $table->string('type')->comment('Developer, Property Management, Residence, Fire Insurance, Vehicle Insurance');
            $table->string('name', 255);
            $table->string('name_th', 255)->nullable();
            $table->string('contact_email', 50)->nullable();
            $table->string('contact_number', 20)->nullable();
            $table->longText('address')->nullable();
            $table->json('person_in_charges')->nullable();
            $table->string('website_url')->nullable();
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
        Schema::dropIfExists('companies');
    }
};
