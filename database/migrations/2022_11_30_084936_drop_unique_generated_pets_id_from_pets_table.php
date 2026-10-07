<?php

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
        Schema::table('pets', function (Blueprint $table) {
            $table->dropUnique('pets_generated_pet_no_unique');
            $table->dropForeign('pets_user_id_foreign');
            $table->integer('year')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pets', function (Blueprint $table) {
            $table->string('generated_pet_no', 18)->nullable()->unique()->change();
            $table->foreignIdFor(User::class)->constrained()->change();
            $table->year('year')->change();
        });
    }
};
