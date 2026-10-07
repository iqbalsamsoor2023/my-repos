<?php

namespace App\Services;

use App\Actions\HealthQuestionnaire\GetHealthQuestionnaireAction;
use Illuminate\Http\Request;

class HealthQuestionnaireService
{
    public function index(Request $request)
    {
        $getHealthQuestionnaireAction = new GetHealthQuestionnaireAction;
        $healthQuestionnaires = $getHealthQuestionnaireAction->execute($request);

        return $healthQuestionnaires;
    }
}
