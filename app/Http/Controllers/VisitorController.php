<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use function Sentry\captureException;
use App\Enums\Visitor\EstampByType;
use App\Enums\Visitor\VisitingArrangementStatus;
use App\Exceptions\GeneralException;
use App\Models\VisitorLog;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class VisitorController extends Controller
{
    /**
     * Display qr code the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function estamp($id)
    {
        try {
            $visitor = VisitorLog::with('visitingArrangements')->findOrFail($id);

            if (! in_array($visitor->status, [
                VisitingArrangementStatus::NOT_MY_VISITOR->value,
                VisitingArrangementStatus::CANCEL_BY_SG->value,
            ])) {
                $visitor->visitingArrangements()
                    ->whereNull('estamp_by')
                    ->update([
                        'status' => VisitingArrangementStatus::MY_VISITOR->value,
                        'estamp_by_type' => EstampByType::PM->value,
                        'estamp_by' => auth()->id(),
                    ]);
            }

            return redirect()->route('filament.admin.resources.visitors.index')->with('flash_success', __('Successfully Updated Visitor'));
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
