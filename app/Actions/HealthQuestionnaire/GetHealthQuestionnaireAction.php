<?php

namespace App\Actions\HealthQuestionnaire;

use App\Models\HealthQuestionnaire;
use Illuminate\Http\Request;

class GetHealthQuestionnaireAction
{
    public function execute(Request $request)
    {
        $healthQuestionnaires = HealthQuestionnaire::with('healthQuestionnaireAnswers');

        if (isset($request->has_pagination) && ($request->has_pagination == false)) {
            return $healthQuestionnaires->get();
        }

        return $healthQuestionnaires->paginate(20);
    }
}
