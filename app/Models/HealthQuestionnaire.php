<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HealthQuestionnaire extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'question',
        'question_th',
        'is_active',
        'created_at',
        'updated_at',
    ];

    /**
     * Get the health questionnaire answers that owns the HealthQuestionnaire.
     *
     * @return HasMany
     */
    public function healthQuestionnaireAnswers(): HasMany
    {
        return $this->hasMany(HealthQuestionnaireAnswer::class);
    }
}
