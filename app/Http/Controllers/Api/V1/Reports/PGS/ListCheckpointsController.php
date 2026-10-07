<?php

namespace App\Http\Controllers\Api\V1\Reports\PGS;

use App\Http\Controllers\Controller;
use App\Models\Sgoc\Checkpoint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ListCheckpointsController extends Controller
{
    public function index(Request $request)
    {
        $residenceId = Auth::user()->propertyManagement->id;
        $checkpoints = Checkpoint::select('id', 'name', 'description')
            ->where('mmb_residence_id', $residenceId)
            ->where('is_active', 1)
            ->paginate(15);

        return $checkpoints;
    }
}
