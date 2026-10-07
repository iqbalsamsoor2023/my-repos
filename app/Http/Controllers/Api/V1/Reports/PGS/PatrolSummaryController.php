<?php

namespace App\Http\Controllers\Api\V1\Reports\PGS;

use App\Actions\PatrolGuard\GetCheckpointSummaryAction;
use App\Actions\PatrolGuard\GetRoundSummaryAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\PatrolSummaryResource;
use App\Models\CheckpointLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PatrolSummaryController extends Controller
{
    public function index(Request $request)
    {
        // Search input
        $inputCheckpointName = $request->query('checkpoint_name');

        if (! empty($request->query('date'))) {
            $searchDate = new Carbon($request->query('date'));
        }

        // Get user residence to retrieve related checkpoints
        $residence = Auth::user()->propertyManagement;

        // Query checkpoint logs and include checkpoint name filter together to avoid more query for optimization
        $checkpointLogs = CheckpointLog::whereHas('checkpoint', function ($query) use ($residence, $inputCheckpointName) {
            $query->where('mmb_residence_id', $residence->id);
            if (! empty($inputCheckpointName)) {
                $query->where('name', 'LIKE', "%$inputCheckpointName%");
            }
        });

        if (isset($searchDate)) {
            $checkpointLogs = $checkpointLogs
                ->where('created_at', '>=', $searchDate)
                ->where('created_at', '<', $searchDate->copy()->addDay(1))
                ->where('created_at', '>=', Carbon::now()->sub('3 months'));
        } elseif (! empty($inputCheckpointName)) {
            $checkpointLogs = $checkpointLogs
                ->where('created_at', '>=', Carbon::now()->sub('3 months'));
        } else {
            $checkpointLogs = $checkpointLogs
                ->where('created_at', '>=', Carbon::now()->sub('7 days'));
        }

        $checkpointLogs = $checkpointLogs->latest('id')->paginate(10);

        return PatrolSummaryResource::collection($checkpointLogs);
    }

    public function getCheckpointSummary(Request $request, GetCheckpointSummaryAction $action)
    {
        return response()->json($action->execute($request->all()));
    }

    public function getRoundSummary(Request $request, GetRoundSummaryAction $action)
    {
        return response()->json($action->execute($request->all()));
    }
    
}
