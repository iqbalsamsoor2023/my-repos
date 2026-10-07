<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Response;
use function Sentry\captureException;
use App\Http\Controllers\Controller;
use App\Interfaces\ThaiNationalIDOCRInterface;
use Exception;
use Illuminate\Http\Request;

class ThaiNationalIDOCRController extends Controller
{
    protected $thaiNationalIDOCRRepository;

    public function __construct(ThaiNationalIDOCRInterface $thaiNationalIDOCRRepository)
    {
        $this->thaiNationalIDOCRRepository = $thaiNationalIDOCRRepository;
    }

    /**
     * Get information from thai national id front card
     *
     * @param Request $request
     * @return Response
     */
    public function front(Request $request)
    {
        try {
            $response = $this->thaiNationalIDOCRRepository->frontSide($request);

            return success($response);
        } catch (Exception $ex) {
            captureException($ex);

            return response()->json(['message' => $ex->getMessage()]);
        }
    }
}
