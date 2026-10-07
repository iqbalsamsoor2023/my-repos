<?php

namespace App\Repositories;

use App\Actions\Auth\ForgotPasswordAction;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Interfaces\AuthRepositoryInterface;

class AuthRepository implements AuthRepositoryInterface
{
    public function forgotPassword(ForgotPasswordRequest $request)
    {
        $forgotPasswordAction = new ForgotPasswordAction;

        return $forgotPasswordAction->execute($request);
    }
}
