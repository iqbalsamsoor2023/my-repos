<?php

use App\Models\Country;
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
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignIdFor(Country::class)->nullable()->constrained();
            $table->string('name');
            $table->string('email')->unique();
            $table->dateTime('email_verified_at')->nullable();
            $table->string('id_number', 20)->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->string('phone_no')->default('000-0000000');
            $table->longText('address')->nullable();
            $table->dateTime('is_community_head_verified')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->tinyInteger('gender')->nullable()->comment('1-Male, 2-Female');
            $table->string('passport_number')->nullable();
            $table->date('passport_expiry')->nullable();
            $table->rememberToken();
            $table->foreignId('current_team_id')->nullable();
            $table->string('profile_photo_path', 2048)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
