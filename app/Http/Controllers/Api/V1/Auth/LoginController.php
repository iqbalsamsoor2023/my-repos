<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ApiLoginRequest;
use App\Http\Resources\Auth\LoginResource;
use App\Models\MasterPassword;
use App\Models\User;
use App\Services\DeviceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class LoginController extends Controller
{
    private $privacy_policy;

    private $terms_and_conditions;

    private $privacy_policy_th;

    private $terms_and_conditions_th;

    public function __construct()
    {
        $baseUrl = config('app.url');

        $this->privacy_policy = $baseUrl.'/privacy';
        $this->terms_and_conditions = $baseUrl.'/terms';
        $this->privacy_policy_th = $baseUrl.'/privacy/th';
        $this->terms_and_conditions_th = $baseUrl.'/terms/th';
    }

    /**
     * @OA\Post(
     *     path="/api/v1/login",
     *     summary="User login",
     *     description="Endpoint for user authentication.",
     *     tags={"Authentication"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="User credentials for login",
     *
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *
     *             @OA\Schema(
     *
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="password", type="string"),
     *             @OA\Property(property="device_id", type="string"),
     *             @OA\Property(property="brand", type="string"),
     *             @OA\Property(property="model", type="string"),
     *             @OA\Property(property="fcm_token", type="string"),
     *             @OA\Property(property="huawei_token", type="string"),
     *             @OA\Property(property="project", type="enum", description="1-gp 2-gpl 3-my mooban 4-pmtalk"),
     *             @OA\Property(property="package_name", type="string"),
     *             )
     *         ),
     *
     *     @OA\MediaType(
     *         mediaType="application/json",
     *
     *         @OA\Schema(
     *
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="password", type="string"),
     *             @OA\Property(property="device_id", type="string"),
     *             @OA\Property(property="brand", type="string"),
     *             @OA\Property(property="model", type="string"),
     *             @OA\Property(property="fcm_token", type="string"),
     *             @OA\Property(property="huawei_token", type="string"),
     *             @OA\Property(property="project", type="string", enum={"project_value_1", "project_value_2"}),
     *             @OA\Property(property="package_name", type="string"),
     *         )
     *     ),
     *     ),
     *
     *     @OA\Response(
     *         response="200",
     *         description="Successful login",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="status", type="string", description="Status"),
     *             @OA\Property(property="message", type="integer", description="Message"),
     *             @OA\Property(property="user", type="object", description="User information",
     *                  @OA\Property(property="id", type="integer"),
     *                  @OA\Property(property="country_id", type="integer"),
     *                  @OA\Property(property="name", type="string"),
     *                  @OA\Property(property="email", type="string"),
     *                  @OA\Property(property="email_verified_at", type="string", format="date-time", nullable=true),
     *                  @OA\Property(property="pdpa_agreed_at", type="string", format="date-time", nullable=true),
     *                  @OA\Property(property="id_number", type="string"),
     *                  @OA\Property(property="two_factor_confirmed_at", type="string", format="date-time", nullable=true),
     *                  @OA\Property(property="phone_no", type="string"),
     *                  @OA\Property(property="address", type="string", nullable=true),
     *                  @OA\Property(property="is_community_head_verified", type="string", format="date-time", nullable=true),
     *                  @OA\Property(property="date_of_birth", type="string", nullable=true),
     *                  @OA\Property(property="gender", type="integer"),
     *                  @OA\Property(property="passport_number", type="string", nullable=true),
     *                  @OA\Property(property="passport_expiry", type="string", format="date-time", nullable=true),
     *                  @OA\Property(property="current_team_id", type="integer", nullable=true),
     *                  @OA\Property(property="profile_photo_path", type="string", nullable=true),
     *                  @OA\Property(property="created_at", type="string", format="date-time"),
     *                  @OA\Property(property="updated_at", type="string", format="date-time"),
     *                  @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true),
     *                  @OA\Property(property="lang", type="string"),
     *                  @OA\Property(property="profile_image_url", type="string"),
     *                  @OA\Property(property="media", type="array",
     *
     *                      @OA\Items(
     *
     *                          @OA\Property(property="id", type="integer"),
     *                          @OA\Property(property="model_type", type="string"),
     *                          @OA\Property(property="model_id", type="integer"),
     *                          @OA\Property(property="uuid", type="string"),
     *                          @OA\Property(property="collection_name", type="string"),
     *                          @OA\Property(property="name", type="string"),
     *                          @OA\Property(property="file_name", type="string"),
     *                          @OA\Property(property="mime_type", type="string"),
     *                          @OA\Property(property="disk", type="string"),
     *                          @OA\Property(property="conversions_disk", type="string"),
     *                          @OA\Property(property="size", type="integer"),
     *                          @OA\Property(property="created_at", type="string", format="date-time"),
     *                          @OA\Property(property="updated_at", type="string", format="date-time"),
     *                          @OA\Property(property="original_url", type="string"),
     *                          @OA\Property(property="preview_url", type="string", nullable=true),
     *                      )
     *                  ),
     *              ),
     *         ),
     *     ),
     *
     *     @OA\Response(
     *         response="401",
     *         description="Unauthorized",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="status", type="boolean", description="status"),
     *             @OA\Property(property="message", type="string", description="Error message"),
     *         )
     *     ),
     * )
     *
     * @return User
     */
    public function login(ApiLoginRequest $request)
    {
        // Master Password authentication
        // 1. If input is master password, check db if exist.
        // 2. If user is super admin role, and using master password, throw error
        // 3. If user is other than super admin roles, and using master password, return true
        $masterPassword = MasterPassword::where('password', $request->password)->exists();
        $user = User::where('email', $request->email)->first();

        // Check if email is verified
        if ($user && is_null($user->email_verified_at)) {
            $platform = $request->header('X-App-Platform') ?? 'N/A';
            $appVersionCode = $request->header('X-App-Version') ?? 0;
            $appName = $request->header('X-App-Name') ?? 'N/A';
            $isUnverifiedFromV3 = Cache::get('user-app-new-user-'.$user->id);

            if ($appName == 'user-app' && ! is_null($isUnverifiedFromV3)) {
                if (($platform == 'android' && $appVersionCode >= env('ANDROID_VERSION_CODE', 276)) || ($platform == 'ios' && $appVersionCode >= env('IOS_VERSION_CODE', 300))) {
                    return success(
                        [
                            'user_id' => $user->id,
                        ],
                        'api-response.error.verify_email_first',
                        JsonResponse::HTTP_UNAUTHORIZED
                    );
                }
            }
        }

        if (! $masterPassword) {
            if (! Auth::attempt($request->only(['email', 'password']))) {
                return response()->json([
                    'status' => false,
                    'message' => __('api-response.error.invalid_credentials'),
                ], 401);
            }
        }

        if ($masterPassword && $user->hasRole('Super Admin')) {
            return response()->json([
                'status' => false,
                'message' => 'Super admin cannot use master password.',
            ], 401);
        }

        if ($masterPassword && ! $user->hasRole('Super Admin')) {
            auth()->login($user);
        }

        $request = $request->merge([
            'user_id' => $user->id,
        ]);

        $deviceService = new DeviceService;
        $deviceService->deviceChecker($request);

        return response()->json([
            'status' => true,
            'message' => 'User Logged In Successfully',
            'user' => (new LoginResource($user))->resolve(),
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/auth/links",
     *     summary="Get Links",
     *     description="Retrieve Links",
     *     tags={"Link"},
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
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="privacy_policy_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                 @OA\Property(property="terms_and_conditions_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                 @OA\Property(property="privacy_policy_url_th", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                 @OA\Property(property="terms_and_conditions_url_th", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *             ),
     *             @OA\Property(property="message", type="string", example="Success"),
     *         )
     *     ),
     * )
     */
    public function links()
    {
        $data = [
            'privacy_policy_url' => $this->privacy_policy ?? null,
            'terms_and_conditions_url' => $this->terms_and_conditions ?? null,
            'privacy_policy_url_th' => $this->privacy_policy_th ?? null,
            'terms_and_conditions_url_th' => $this->terms_and_conditions_th ?? null,
        ];

        return success($data);
    }
}
