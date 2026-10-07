<?php

namespace App\Services;

use App\Actions\FrequentlyAskQuestion\GetFrequentlyAskQuestionAction;
use Illuminate\Http\Request;

class FrequentlyAskQuestionService
{
    public function index(Request $request)
    {
        $getFrequentlyAskQuestionAction = new GetFrequentlyAskQuestionAction;
        $frequentlyAskQuestion = $getFrequentlyAskQuestionAction->execute($request);

        return $frequentlyAskQuestion;
    }
}
