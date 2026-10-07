<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Response;
use function Sentry\captureException;
use App\Http\Controllers\Controller;
use App\Interfaces\LPRRepositoryInterface;
use Exception;
use Illuminate\Http\Request;

class LPRController extends Controller
{
    protected $lprRepository;

    public function __construct(LPRRepositoryInterface $lprRepository)
    {
        $this->lprRepository = $lprRepository;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        try {
            $response = $this->lprRepository->create($request);

            return success($response);
        } catch (Exception $ex) {
            captureException($ex);

            return response()->json(['message' => $ex->getMessage()]);
        }
    }
}
