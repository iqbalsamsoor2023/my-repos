<?php

namespace App\Http\Controllers\Auth;

use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\MasterPassword;
use App\Models\Residence;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use DateTime;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     *
     * @return View
     */
    public function create()
    {
        return view('auth.filament-login');
    }

    /**
     * Handle an incoming authentication request.
     *
     * @param LoginRequest $request
     * @return RedirectResponse
     */
    public function store(LoginRequest $request)
    {
        // Master Password authentication
        // 1. If input is master password, check db if exist.
        // 2. If user is super admin role, and using master password, throw error
        // 3. If user is other than super admin roles, and using master password, return true
        $masterPassword = MasterPassword::where('password', $request->password)->exists();
        $user = User::where('email', $request->email)->first();

        if (is_null($user)) {
            throw ValidationException::withMessages([
                'email' => 'User not found or module is not activated',
            ]);
        }

        $todayDate = new DateTime;

        if (! $masterPassword) {
            if ($user->hasRole('Property Management') == true) {
                $residence = Residence::where('property_management_user_id', $user->id)->first();

                if ($residence) {
                    if (! empty($residence->subscription_end_date) && $residence->subscription_end_date < $todayDate->format('Y-m-d')) {
                        throw ValidationException::withMessages([
                            'message' => trans(__('app.your_account_has_expired').' on '.$residence->subscription_end_date),
                        ]);
                    }
                }

                // Create DateTime object for the subscription end date
                $subscriptionEndDate = new DateTime($residence->subscription_end_date);

                // Calculate three months before the subscription end date
                $threeMonthsBeforeEndDate = clone $subscriptionEndDate;
                $threeMonthsBeforeEndDate->modify('-3 months');

                // Check if today's date is within the three months before the subscription ends
                if ($todayDate >= $threeMonthsBeforeEndDate && $todayDate < $subscriptionEndDate) {
                    Notification::make()
                        ->warning()
                        ->title('Warning!')
                        ->body(__('app.your_account_will_expire_on').' '.$residence->subscription_end_date)
                        ->send();
                }
            }

            $request->authenticate();
            $request->session()->regenerate();

            return redirect()->intended(RouteServiceProvider::HOME);
        }

        if ($masterPassword && $user->hasRole('Super Admin')) {
            throw ValidationException::withMessages([
                'email' => trans('auth.super_admin_cannot_use_master_password'),
            ]);
        }

        if ($masterPassword && ! $user->hasRole('Super Admin')) {
            auth()->login($user);
            $request->session()->regenerate();

            return redirect()->intended(RouteServiceProvider::HOME);
        }
    }

    /**
     * Destroy an authenticated session.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
