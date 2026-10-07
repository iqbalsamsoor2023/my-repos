<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use function Sentry\captureException;
use App\Exceptions\GeneralException;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Cache;

class UserController extends Controller
{
    /**
     * verify user resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function verification(int $id)
    {
        try {
            $user = User::whereId($id)->first();

            if (is_null($user->email_verified_at) == true) {
                $user->email_verified_at = now();
                $user->save();

                $status = 'Your e-mail has been verified. Please login to continue.';

                // delete the cache key for email verification
                Cache::forget('user-app-new-user-'.$user->id);
            } else {
                $status = 'Your e-mail is already verified. Please login to continue.';
            }

            return view('users.email-verification', compact('status'));
        } catch (ModelNotFoundException $ex) {
            captureException($ex);

            return response()->json(['message' => $ex->getMessage()]);
        } catch (GeneralException $ex) {
            captureException($ex);

            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            captureException($ex);

            return response()->json(['message' => $ex->getMessage()]);
        }
    }
}
