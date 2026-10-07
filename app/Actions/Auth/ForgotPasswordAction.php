<?php

namespace App\Actions\Auth;

use App\Http\Requests\Auth\ForgotPasswordRequest;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class ForgotPasswordAction
{
    public function execute(ForgotPasswordRequest $request)
    {
        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status == Password::RESET_LINK_SENT) {
            return $status;
        }

        throw ValidationException::withMessages([
            'email' => [trans($status)],
        ]);
    }
}
