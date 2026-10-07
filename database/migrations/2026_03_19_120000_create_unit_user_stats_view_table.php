<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_user_stats_view', function (Blueprint $table) {
            $table->unsignedBigInteger('unit_user_id')->primary();
            $table->unsignedBigInteger('unit_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('residence_id')->index();

            $table->smallInteger('mooban_type')->nullable()->index();
            $table->smallInteger('sub_type')->nullable()->index();
            $table->unsignedSmallInteger('residence_activation_status_id')->nullable()->index();
            $table->string('main_road')->nullable();
            $table->unsignedBigInteger('subdistrict_id')->nullable()->index();
            $table->unsignedBigInteger('district_id')->nullable()->index();
            $table->unsignedBigInteger('province_id')->nullable()->index();

            $table->string('unit_number')->nullable();
            $table->string('email')->nullable();
            $table->unsignedBigInteger('country_id')->nullable()->index();
            $table->string('country_name')->nullable();
            $table->unsignedTinyInteger('gender')->nullable()->index();
            $table->date('user_date_of_birth')->nullable();
            $table->string('widget_age_group', 20)->nullable()->index();
            $table->string('table_age_group', 20)->nullable()->index();

            $table->boolean('is_owner')->default(false)->index();
            $table->boolean('is_main_owner')->default(false);
            $table->boolean('is_main_tenant')->default(false);
            $table->unsignedSmallInteger('approval_status')->nullable();
            $table->boolean('is_email_verified')->default(false);

            $table->timestamp('unit_user_created_at')->nullable()->index();
            $table->timestamp('unit_user_updated_at')->nullable()->index();
            $table->timestamp('unit_user_deleted_at')->nullable()->index();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->index(['province_id', 'mooban_type']);
            $table->index(['district_id', 'mooban_type']);
            $table->index(['residence_activation_status_id', 'mooban_type'], 'uusv_ras_mooban_index');
            $table->index(['residence_id', 'unit_user_created_at'], 'uusv_residence_created_index');
            $table->index(['residence_id', 'country_id'], 'uusv_residence_country_index');
            $table->index(['residence_id', 'gender'], 'uusv_residence_gender_index');
            $table->index(['residence_id', 'is_owner'], 'uusv_residence_owner_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_user_stats_view');
    }
};
