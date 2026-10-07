<?php

namespace App\Http\Controllers\Api\V1\Auth;

use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Interfaces\AuthRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    private AuthRepositoryInterface $authRepositoryInterface;

    public function __construct(AuthRepositoryInterface $authRepositoryInterface)
    {
        $this->authRepositoryInterface = $authRepositoryInterface;
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @param Request $request
     * @return RedirectResponse
     *
     * @throws ValidationException
     */
    public function store(ForgotPasswordRequest $request)
    {
        $status = $this->authRepositoryInterface->forgotPassword($request);

        return response()->json(['message' => trans($status)], 200);
    }
}
