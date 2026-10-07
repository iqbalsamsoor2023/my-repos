<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\InsuranceCompany\GetInsuranceCompanyRequest;
use App\Http\Resources\InsuranceCompanyResource;
use App\Models\InsuranceCompany;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InsuranceCompanyController extends Controller
{
    /**
     * Get a list of active insurance companies.
     *
     * @OA\Get(
     *     path="/api/v1/insurance-companies",
     *     summary="Get active insurance companies",
     *     description="Returns a list of insurance companies where is_active = 1",
     *     operationId="getInsuranceCompanies",
     *     tags={"Insurance Companies"},
     *     security={{"bearer_token": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *
     *         @OA\JsonContent(
     *             type="array",
     *
     *             @OA\Items(
     *
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Thai Life Insurance"),
     *                 @OA\Property(property="type", type="string", example="life_insurance"),
     *                 @OA\Property(property="website_url", type="string", example="https://example.com")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function index(GetInsuranceCompanyRequest $request): AnonymousResourceCollection
    {
        $query = InsuranceCompany::where('is_active', true);

        if ($type = $request->validated('type')) {
            $query->where('type', $type);
        }

        $companies = $query->get(['id', 'name', 'type', 'website_url']);

        return InsuranceCompanyResource::collection($companies);
    }
}
