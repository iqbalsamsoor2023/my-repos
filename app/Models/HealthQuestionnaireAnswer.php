<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HealthQuestionnaireAnswer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'health_questionnaire_id',
        'answer',
        'answer_th',
        'created_at',
        'updated_at',
    ];

    /**
     * Get the health questionnaire that owns the HealthQuestionnaireAnswer.
     *
     * @return BelongsTo
     */
    public function healthQuestionnaire(): BelongsTo
    {
        return $this->belongsTo(HealthQuestionnaire::class);
    }
}
