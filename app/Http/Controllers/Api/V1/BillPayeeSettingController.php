<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ResidenceBankAccountListRequest;
use App\Http\Resources\ResidenceBankAccountResource;
use App\Repositories\BillPayeeSettingRepository;

class BillPayeeSettingController extends Controller
{
    protected $billPayeeSettingRepository;

    public function __construct(BillPayeeSettingRepository $billPayeeSettingRepository)
    {
        $this->billPayeeSettingRepository = $billPayeeSettingRepository;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/bank-accounts",
     *     summary="Get bank accounts",
     *     description="Endpoint to retrieve bank accounts.",
     *     tags={"Banks"},
     *     security={
     *       {"bearer_token": {}}
     *     },
     *
     *     @OA\Parameter(
     *         name="Accept-Language",
     *         in="header",
     *         required=false,
     *
     *         @OA\Schema(
     *             type="string",
     *             example="en-US"
     *         ),
     *         description="The language of the response"
     *     ),
     *
     *     @OA\Response(
     *         response="200",
     *         description="Successful response"
     *     ),
     *     @OA\Response(
     *         response="401",
     *         description="Unauthorized"
     *     )
     * )
     */
    public function residenceBankAccountList(ResidenceBankAccountListRequest $request)
    {
        return success(ResidenceBankAccountResource::collection($this->billPayeeSettingRepository->getResidenceBankAccountList($request)));
    }
}
