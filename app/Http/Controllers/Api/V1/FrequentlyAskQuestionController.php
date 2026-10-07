<?php

namespace App\Http\Controllers\Api\V1;

use Exception;
use App\Http\Controllers\Controller;
use App\Services\FrequentlyAskQuestionService;
use Illuminate\Http\Request;

class FrequentlyAskQuestionController extends Controller
{
    protected $service;

    public function __construct(FrequentlyAskQuestionService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        try {
            $response = $this->service->index($request);

            return $response;
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()]);
        }
    }
}
