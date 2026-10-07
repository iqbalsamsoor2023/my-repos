<?php

namespace App\Rules;

use Closure;
use Illuminate\Translation\PotentiallyTranslatedString;
use App\Models\BillPayeeSetting;
use Illuminate\Contracts\Validation\InvokableRule;

class BillSetting implements InvokableRule
{
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
        $billSetting = BillPayeeSetting::where('residence_id', $value)->first();

        // $route = route('filament.resources.bill-reminder-settings.create');

        if (! $billSetting) {
            // $fail('You need to add Bill Reminder Setting for this :attribute before proceed! Click this link to proceed: ' . redirect()->$route);
            $fail('You need to add Bill Reminder Setting for this :attribute before proceed!');
        }
    }
}
