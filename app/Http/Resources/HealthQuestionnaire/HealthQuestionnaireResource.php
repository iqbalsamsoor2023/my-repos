<?php

namespace App\Http\Resources\HealthQuestionnaire;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HealthQuestionnaireResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'question' => $this->question,
            'question_th' => $this->question_th,
            'is_active' => $this->is_active,
            'health_questionnaire_answers' => $this->healthQuestionnaireAnswers->map(function ($answer) {
                return [
                    'id' => $answer->id,
                    'health_questionnaire_id' => $answer->health_questionnaire_id,
                    'answer' => $answer->answer,
                    'answer_th' => $answer->answer_th,
                ];
            }),
        ];
    }
}
