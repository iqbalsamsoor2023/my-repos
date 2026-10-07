<?php

namespace App\Actions\Visitor;

use Exception;
use App\Exceptions\GeneralException;
use App\Helpers\VisitorHelper;
use App\Http\Requests\Visitor\StoreVisitorRequest;
use App\Models\Visitor;
use App\Models\VisitorLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CreateVisitorLogAction
{
    public function execute(StoreVisitorRequest $request, Visitor $visitor)
    {
        return $this->createVisitorLog($request, $visitor);
    }

    public function createVisitorLog(StoreVisitorRequest $request, Visitor $visitor)
    {
        $attempts = 0;
        $visitor_generated_no = null;

        do {
            $visitor_generated_no = VisitorHelper::generateVisitorNo($request->residence_id);

            try {
                $request->merge([
                    'visitor_id' => $visitor->id,
                    'visitor_generated_no' => $visitor_generated_no,
                    'arrival_time' => now(),
                    'visitor_code' => isset($request->visitor_code) ? $request->visitor_code : Str::random(20),
                ]);

                $visitor_log = VisitorLog::create($request->only([
                    'visitor_id',
                    'visitor_card_id',
                    'visitor_purpose',
                    'visitor_generated_no',
                    'visitor_code',
                    'company_name',
                    'arrival_type',
                    'vehicle_type',
                    'vehicle_plate_no',
                    'arrival_time',
                    'temperature',
                    'passenger_count',
                    'remark',
                    'is_allowed',
                    'blacklist_remark',
                    'is_pre_register',
                    'vehicle_info',
                ]));

                break;
            } catch (Exception $th) {
                $attempts++;
                DB::rollBack();

                Log::build([
                    'driver' => 'single',
                    'path' => storage_path('logs/vms_logs.log'),
                ])->info($th);

                throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Failed creating visitor log. '.$th->getMessage());
            }
        } while ($attempts <= 5);

        $this->uploadIdImage($visitor_log, $request);
        $this->uploadVisitorImage($visitor_log, $request);
        $this->uploadVehicleImage($visitor_log, $request);
        $this->uploadEsignImage($visitor_log, $request);

        return $visitor_log;
    }

    private function uploadIdImage(VisitorLog $visitor_log, $request)
    {
        if ($request->hasFile('id_image')) {
            $visitor_log->addMediaFromRequest('id_image')->withCustomProperties(['type' => 'id_image'])->toMediaCollection('id_image');
        }
    }

    private function uploadVisitorImage(VisitorLog $visitor_log, $request)
    {
        if ($request->hasFile('visitor_image')) {
            $visitor_log->addMediaFromRequest('visitor_image')->withCustomProperties(['type' => 'visitor_image'])->toMediaCollection('visitor_image');
        }
    }

    private function uploadVehicleImage(VisitorLog $visitor_log, $request)
    {
        if ($request->hasFile('vehicle_image')) {
            $visitor_log->addMediaFromRequest('vehicle_image')->withCustomProperties(['type' => 'vehicle_image'])->toMediaCollection('vehicle_image');
        }
    }

    private function uploadEsignImage(VisitorLog $visitor_log, $request)
    {
        if ($request->hasFile('esign_image')) {
            $visitor_log->addMediaFromRequest('esign_image')->withCustomProperties(['type' => 'pdpa_esign'])->toMediaCollection('esign_image');
        }
    }
}
