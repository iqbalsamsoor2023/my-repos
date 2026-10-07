<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use function Sentry\captureException;
use App\Exceptions\GeneralException;
use App\Helpers\InvitationCodeGenerator;
use App\Models\Unit;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UnitController extends Controller
{
    /**
     * Re-generate invitation code owner.
     *
     * @param  int  $id
     * @return Response
     */
    public function regenerateInvitationCodeOwner(int $id)
    {
        try {
            $unit = Unit::findOrFail($id);
            $unit->invitation_code_owner = InvitationCodeGenerator::generate($unit, 'owner');
            $unit->save();

            return response()->json([
                'invitation_code_owner' => $unit->invitation_code_owner,
            ]);
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

    /**
     * Re-generate invitation code tenant.
     *
     * @param  int  $id
     * @return Response
     */
    public function regenerateInvitationCodeTenant(int $id)
    {
        try {
            $unit = Unit::findOrFail($id);
            $unit->invitation_code_tenant = InvitationCodeGenerator::generate($unit, 'tenant');
            $unit->save();

            return response()->json([
                'invitation_code_tenant' => $unit->invitation_code_tenant,
            ]);
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
