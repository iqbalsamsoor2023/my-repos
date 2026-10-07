<?php

namespace App\Rules;

use Closure;
use Illuminate\Translation\PotentiallyTranslatedString;
use App\Models\BlacklistedVisitor;
use App\Models\Visitor;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\InvokableRule;

class BlacklistVistor implements DataAwareRule, InvokableRule
{
    /**
     * All of the data under validation.
     *
     * @var array
     */
    protected $data = [];

    /**
     * Set the data under validation.
     *
     * @param  array  $data
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Run the validation rule.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param Closure(string):PotentiallyTranslatedString $fail
     * @return void
     */
    public function __invoke($attribute, $value, $fail)
    {
        $visitor = Visitor::where('id_number', $value)->first();

        if ($visitor) {
            $blacklistedVisitor = BlacklistedVisitor::where('residence_id', $this->data['data']['residence_id'])
                ->where('visitor_id', $visitor->id)->first();

            if ($blacklistedVisitor) {
                $fail('This visitor already have record in the blacklisted!');
            }
        }
    }
}
