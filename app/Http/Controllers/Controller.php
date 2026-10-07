<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 ** @OA\Info(
 *    title="MyMooBan",
 *    version="1.0.0",
 * )
 *
 * @OA\SecurityScheme(
 *     type="http",
 *     securityScheme="bearer_token",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 *
 *  * @OA\Post(
 *     path="/oauth/token",
 *     summary="Generate a new access token",
 *     description="Generate a new access token using Laravel Passport.",
 *     tags={"Authentication"},
 *
 *     @OA\RequestBody(
 *         required=true,
 *         description="Credentials for generating an access token",
 *
 *         @OA\MediaType(
 *             mediaType="application/x-www-form-urlencoded",
 *
 *             @OA\Schema(
 *
 *                 @OA\Property(property="grant_type", type="string", description="Grant type (password)"),
 *                 @OA\Property(property="client_id", type="string", description="Client ID"),
 *                 @OA\Property(property="client_secret", type="string", description="Client Secret"),
 *                 @OA\Property(property="username", type="string", description="Username"),
 *                 @OA\Property(property="password", type="string", description="Password"),
 *                 @OA\Property(property="scope", type="string", description="Scopes (optional)"),
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response="200",
 *         description="Successful token generation",
 *
 *         @OA\JsonContent(
 *
 *             @OA\Property(property="token_type", type="string", description="Bearer"),
 *             @OA\Property(property="expires_in", type="integer", description="Token expiration time in seconds"),
 *             @OA\Property(property="access_token", type="string", description="Generated access token"),
 *             @OA\Property(property="refresh_token", type="string", description="Refresh token"),
 *         )
 *     ),
 *
 *  @OA\Response(
 *         response="400",
 *         description="Bad Request",
 *
 *         @OA\JsonContent(
 *
 *             @OA\Property(property="error", type="string", description="Error message"),
 *             @OA\Property(property="error_description", type="string", description="Error description"),
 *             @OA\Property(property="message", type="string", description="Message"),
 *         )
 *     ),
 * )
 */
class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
}
