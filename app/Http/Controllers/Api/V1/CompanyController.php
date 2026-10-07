<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Company\GetCompanyRequest;
use App\Repositories\CompanyRepository;
use Exception;

class CompanyController extends Controller
{
    protected $companyRepository;

    public function __construct(CompanyRepository $companyRepository)
    {
        $this->companyRepository = $companyRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @OA\Get(
     *     path="/api/v1/companies",
     *     summary="Get Company Information",
     *     description="Retrieve company information based on ID, type, name, and contact email",
     *     tags={"Companies"},
     *     security={
     *         {"bearer_token": {}}
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
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Type of the company",
     *
     *         @OA\Schema(
     *             type="string",
     *             enum={"Developer", "Property Management", "Residence", "Fire Insurance", "Vehicle Insurance"}
     *         )
     *     ),
     *
     *     @OA\Parameter(
     *         name="name",
     *         in="query",
     *         description="Name of the company",
     *         required=false,
     *
     *         @OA\Schema(type="string", example="MI Developer")
     *     ),
     *
     *     @OA\Parameter(
     *         name="contact_email",
     *         in="query",
     *         description="Contact email of the company",
     *         required=false,
     *
     *         @OA\Schema(type="string", format="email", example="contact@example.com")
     *     ),
     *
     *     @OA\Parameter(
     *         name="has_pagination",
     *         in="query",
     *         description="Fill in if need data without pagination",
     *         required=false,
     *
     *         @OA\Schema(type="integer", example=0)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Invalid or missing authentication"
     *     ),
     * )
     *
     * @return Response
     */
    public function index(GetCompanyRequest $request)
    {
        try {
            $response = $this->companyRepository->index($request);

            return success($response);
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @OA\Get(
     *     path="/api/v1/companies/{id}",
     *     summary="Show Company Information",
     *     description="Show information for a specific company based on ID",
     *     tags={"Companies"},
     *     security={
     *         {"bearer_token": {}}
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
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the company",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=123)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful response"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Invalid or missing authentication"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found - Company not found"
     *     ),
     * )
     *
     * @param  int  $id
     * @return Response
     */
    public function show(int $id)
    {
        try {
            $response = $this->companyRepository->show($id);

            return success($response);
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()]);
        }
    }
}
