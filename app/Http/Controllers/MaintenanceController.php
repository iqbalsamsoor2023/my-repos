<?php

namespace App\Http\Controllers;

use function Sentry\captureException;
use App\Exceptions\GeneralException;
use App\Exports\MaintenanceReports\PrivateMaintenanceReportDetail;
use App\Exports\MaintenanceReports\PublicMaintenanceReportDetail;
use Exception;

class MaintenanceController extends Controller
{
    public function exportPrivateMaintenanceDetailReport(int $id)
    {
        try {
            return new PrivateMaintenanceReportDetail($id);
        } catch (GeneralException $ex) {
            captureException($ex);

            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            captureException($ex);

            return response()->json(['message' => $ex->getMessage()]);
        }
    }

    public function exportPublicMaintenanceDetailReport(int $id)
    {
        try {
            return new PublicMaintenanceReportDetail($id);
        } catch (GeneralException $ex) {
            captureException($ex);

            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            captureException($ex);

            return response()->json(['message' => $ex->getMessage()]);
        }
    }
}
