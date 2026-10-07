<?php

use App\Models\HealthQuestionnaire;
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
        Schema::create('health_questionnaire_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(HealthQuestionnaire::class)->constrained();
            $table->longText('answer');
            $table->longText('answer_th')->nullable();
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
        Schema::dropIfExists('health_questionnaire_answers');
    }
};
