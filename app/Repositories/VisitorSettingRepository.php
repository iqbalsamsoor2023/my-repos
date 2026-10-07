<?php

namespace App\Repositories;

use App\Actions\VisitorSetting\GetVisitorSettingAction;
use Illuminate\Http\Request;

class VisitorSettingRepository
{
    public function index(Request $request)
    {
        $getVisitorSettingAction = new GetVisitorSettingAction;
        $visitorSettings = $getVisitorSettingAction->execute($request);

        return $visitorSettings;
    }
}
