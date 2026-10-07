<?php

namespace App\Interfaces;

use App\Http\Requests\Auth\ForgotPasswordRequest;

interface AuthRepositoryInterface
{
    public function forgotPassword(ForgotPasswordRequest $request);
}
