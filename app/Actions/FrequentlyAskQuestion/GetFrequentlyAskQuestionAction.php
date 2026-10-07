<?php

namespace App\Actions\FrequentlyAskQuestion;

use App\Http\Integrations\MmbErp\FrequentlyAskQuestion\Requests\GetFaqRequest;
use Illuminate\Http\Request;

class GetFrequentlyAskQuestionAction
{
    public function execute(Request $request)
    {
        $request = new GetFaqRequest($request->all());
        $response = $request->send();

        return $response->json();
    }
}
