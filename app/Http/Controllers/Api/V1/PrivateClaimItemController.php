<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Services\PrivateClaimItemService;
use Exception;
use Illuminate\Http\Request;

class PrivateClaimItemController extends Controller
{
    protected $service;

    public function __construct(PrivateClaimItemService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $response = $this->service->index($request);

            return success($response);
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()]);
        }
    }
}
