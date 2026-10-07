<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Response;
use function Sentry\captureException;
use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Http\Requests\UnitTenant\StoreUnitTenantRequest;
use App\Http\Requests\UnitTenant\UpdateUnitTenantRequest;
use App\Interfaces\UnitTenantRepositoryInterface;
use Exception;

class UnitTenantController extends Controller
{
    protected $unitTenantRepository;

    public function __construct(UnitTenantRepositoryInterface $unitTenantRepository)
    {
        $this->unitTenantRepository = $unitTenantRepository;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @OA\Post(
     *     path="/api/v1/unit-tenants",
     *     summary="Store unit tenant",
     *     description="Store tenant information for a specific unit.",
     *     operationId="storeUnitTenant",
     *     tags={"Unit Tenants"},
     *     security={{"bearer_token": {}}},
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
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\MediaType(
     *             mediaType="application/json",
     *
     *             @OA\Schema(
     *
     *                 @OA\Property(property="country_id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="email", type="string", format="email"),
     *                 @OA\Property(property="id_number", type="string"),
     *                 @OA\Property(property="password", type="string"),
     *                 @OA\Property(property="phone_no", type="string"),
     *                 @OA\Property(property="address", type="string"),
     *                 @OA\Property(property="date_of_birth", type="string", format="date"),
     *                 @OA\Property(property="gender", type="string", enum={"male", "female", "other"}),
     *                 @OA\Property(property="passport_number", type="string"),
     *                 @OA\Property(property="passport_expiry", type="string", format="date"),
     *                 @OA\Property(property="email_verified_at", type="string", format="date-time"),
     *                 @OA\Property(property="pdpa_agreed_at", type="string", format="date-time"),
     *                 @OA\Property(property="unit_id", type="integer"),
     *                 @OA\Property(property="user_id", type="integer"),
     *                 @OA\Property(property="relationship", type="string"),
     *                 @OA\Property(property="is_owner", type="boolean"),
     *                 @OA\Property(property="mmb_id", type="string"),
     *                 @OA\Property(property="approval_status", type="string", enum={"pending", "approved", "rejected"}),
     *             )
     *         ),
     *
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *
     *             @OA\Schema(
     *
     *                 @OA\Property(property="country_id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="email", type="string", format="email"),
     *                 @OA\Property(property="id_number", type="string"),
     *                 @OA\Property(property="password", type="string"),
     *                 @OA\Property(property="phone_no", type="string"),
     *                 @OA\Property(property="address", type="string"),
     *                 @OA\Property(property="date_of_birth", type="string", format="date"),
     *                 @OA\Property(property="gender", type="string", enum={"male", "female", "other"}),
     *                 @OA\Property(property="passport_number", type="string"),
     *                 @OA\Property(property="passport_expiry", type="string", format="date"),
     *                 @OA\Property(property="email_verified_at", type="string", format="date-time"),
     *                 @OA\Property(property="pdpa_agreed_at", type="string", format="date-time"),
     *                 @OA\Property(property="unit_id", type="integer"),
     *                 @OA\Property(property="user_id", type="integer"),
     *                 @OA\Property(property="relationship", type="string"),
     *                 @OA\Property(property="is_owner", type="boolean"),
     *                 @OA\Property(property="mmb_id", type="string"),
     *                 @OA\Property(property="approval_status", type="string", enum={"pending", "approved", "rejected"}),
     *             )
     *         ),
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="message", type="string", example="Tenant information stored successfully"),
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable Entity"
     *     ),
     * )
     *
     * @param StoreUnitTenantRequest $request
     * @return Response
     */
    public function store(StoreUnitTenantRequest $request)
    {
        try {
            $response = $this->unitTenantRepository->create($request);

            return success($response);
        } catch (GeneralException $ex) {
            captureException($ex);

            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            captureException($ex);

            return response()->json(['message' => $ex->getMessage()]);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @OA\Put(
     *     path="/api/v1/unit-tenants/{id}",
     *     summary="Update unit tenant",
     *     description="Update tenant information for a specific unit.",
     *     operationId="updateUnitTenant",
     *     tags={"Unit Tenants"},
     *     security={{"bearer_token": {}}},
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
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\MediaType(
     *             mediaType="application/json",
     *
     *             @OA\Schema(
     *
     *                 @OA\Property(property="country_id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="email", type="string", format="email"),
     *                 @OA\Property(property="id_number", type="string"),
     *                 @OA\Property(property="password", type="string"),
     *                 @OA\Property(property="phone_no", type="string"),
     *                 @OA\Property(property="address", type="string"),
     *                 @OA\Property(property="date_of_birth", type="string", format="date"),
     *                 @OA\Property(property="gender", type="string", enum={"male", "female", "other"}),
     *                 @OA\Property(property="passport_number", type="string"),
     *                 @OA\Property(property="passport_expiry", type="string", format="date"),
     *                 @OA\Property(property="email_verified_at", type="string", format="date-time"),
     *                 @OA\Property(property="pdpa_agreed_at", type="string", format="date-time"),
     *                 @OA\Property(property="unit_id", type="integer"),
     *                 @OA\Property(property="user_id", type="integer"),
     *                 @OA\Property(property="relationship", type="string"),
     *                 @OA\Property(property="is_owner", type="boolean"),
     *                 @OA\Property(property="mmb_id", type="string"),
     *                 @OA\Property(property="approval_status", type="string", enum={"pending", "approved", "rejected"}),
     *             )
     *         ),
     *
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *
     *             @OA\Schema(
     *
     *                 @OA\Property(property="country_id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="email", type="string", format="email"),
     *                 @OA\Property(property="id_number", type="string"),
     *                 @OA\Property(property="password", type="string"),
     *                 @OA\Property(property="phone_no", type="string"),
     *                 @OA\Property(property="address", type="string"),
     *                 @OA\Property(property="date_of_birth", type="string", format="date"),
     *                 @OA\Property(property="gender", type="string", enum={"male", "female", "other"}),
     *                 @OA\Property(property="passport_number", type="string"),
     *                 @OA\Property(property="passport_expiry", type="string", format="date"),
     *                 @OA\Property(property="email_verified_at", type="string", format="date-time"),
     *                 @OA\Property(property="pdpa_agreed_at", type="string", format="date-time"),
     *                 @OA\Property(property="unit_id", type="integer"),
     *                 @OA\Property(property="user_id", type="integer"),
     *                 @OA\Property(property="relationship", type="string"),
     *                 @OA\Property(property="is_owner", type="boolean"),
     *                 @OA\Property(property="mmb_id", type="string"),
     *                 @OA\Property(property="approval_status", type="string", enum={"pending", "approved", "rejected"}),
     *             )
     *         ),
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="message", type="string", example="Tenant information updated successfully"),
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found",
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable Entity"
     *     ),
     * )
     *
     * @param UpdateUnitTenantRequest $request
     * @param  int  $id
     * @return Response
     */
    public function update(UpdateUnitTenantRequest $request, int $id)
    {
        try {
            $response = $this->unitTenantRepository->update($request, $id);

            return success($response);
        } catch (GeneralException $ex) {
            captureException($ex);

            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            captureException($ex);

            return response()->json(['message' => $ex->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @OA\Delete(
     *     path="/api/v1/unit-tenants/{id}",
     *     summary="Delete unit tenant",
     *     description="Delete tenant information for a specific unit.",
     *     operationId="deleteUnitTenant",
     *     tags={"Unit Tenants"},
     *     security={{"bearer_token": {}}},
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
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="message", type="string", example="Tenant information deleted successfully"),
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found",
     *     ),
     * )
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy(int $id)
    {
        try {
            $response = $this->unitTenantRepository->delete($id);

            return success($response);
        } catch (GeneralException $ex) {
            captureException($ex);

            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            captureException($ex);

            return response()->json(['message' => $ex->getMessage()]);
        }
    }
}
