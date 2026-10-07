<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use function Sentry\captureException;
use App\Exceptions\GeneralException;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    /**
     * Update user profile.
     *
     * @param  int  $id
     * @return Response
     */
    public function update(Request $request, int $id)
    {
        try {
            $user = User::findOrFail($id);

            if (is_null($request->new_password) == false) {
                $user->password = Hash::make($request->new_password);
            }

            if (is_null($request->image_base64) == false) {
                $user->clearMediaCollection();
                $user->addMediaFromBase64($request->image_base64)
                    ->usingFileName(Str::random().'.png')
                    ->toMediaCollection();
            }

            $user->save();

            return redirect()->route('filament.admin.resources.users.profile', ['record' => $user->id]);
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
