<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserHealth extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'blood_type',
        'height',
        'weight',
        'health_questionnaire_answers',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'health_questionnaire_answers' => 'array',
    ];

    /**
     * Get the user that owns the UserHealth.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Accessor to get the BMI of the UserHealth.
     *
     * @return Attribute
     */
    protected function getBmiAttribute(): string
    {
        $bmi = '';

        if (isset($this->height) && isset($this->weight)) {
            $bmi = round(($this->weight / $this->height / $this->height) * 10000, 2);
        }

        return $bmi;
    }

    /**
     * Accessor to get the BMI of the UserHealth.
     *
     * @return Attribute
     */
    protected function getBmiCategoryAttribute(): string
    {
        $bmi_category = '';

        if (isset($this->height) && isset($this->weight)) {
            $bmi = round(($this->weight / $this->height / $this->height) * 10000, 2);

            if ($bmi < 18.5) {
                $bmi_category = 'Underweight';
            } elseif ($bmi >= 18.5 || $bmi <= 24.9) {
                $bmi_category = 'Healthy';
            } elseif ($bmi >= 25 || $bmi <= 29.9) {
                $bmi_category = 'Overweight';
            } else {
                $bmi_category = 'Obese';
            }
        }

        return $bmi_category;
    }

    /**
     * Interact with the health questionnaire answers
     *
     * @param  string  $value
     * @return Attribute
     */
    public function healthQuestionnaireAnswers(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => json_decode($value),
            set: fn ($value) => $value,
        );
    }

    /**
     * Interact with the bmi
     *
     * @param  string  $value
     * @return Attribute
     */
    public function bmi(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (isset($this->height) && isset($this->weight)) {
                    return round(($this->weight / $this->height / $this->height) * 10000, 2);
                }
            },
        );
    }

    /**
     * Interact with the date of birth
     *
     * @param  string  $value
     * @return Attribute
     */
    public function dateOfBirth(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value->format('d-m-Y'),
        );
    }
}
