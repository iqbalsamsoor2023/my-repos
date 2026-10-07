<?php

namespace App\Services;

use App\Exceptions\GeneralException;
use App\Http\Requests\Warranty\CreateWarrantyReminderFeedbackRequest;
use App\Http\Requests\Warranty\WarrantyReminderRequest;
use App\Models\PrivateClaimItemSetting;
use App\Models\Unit;
use App\Models\WarrantyReminder;
use App\Models\WarrantySetting;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class WarrantyService
{
    public function reminder(WarrantyReminderRequest $request)
    {
        $warrantySetting = WarrantySetting::where('residence_id', $request->residence_id)->first();

        if (! $warrantySetting) {
            throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, __('maintenance.warranty_setting_setup_message'));
        }

        if ($warrantySetting->has_warranty_reminder == true) {
            $unit = Unit::find($request->unit_id);
            $privateClaimItemSettings = PrivateClaimItemSetting::where('residence_id', $request->residence_id)->get();

            $dont_remind_this_user = WarrantyReminder::where('user_id', $request->user_id)->where('unit_id', $unit->id)->exists();

            if (isset($unit->move_in_at) && ! ($dont_remind_this_user)) {
                foreach ($privateClaimItemSettings as $privateClaimItemSetting) {
                    $warrantyExpiredDate = Carbon::parse($unit->move_in_at)->addMonths($privateClaimItemSetting->warranty_period)->format('Y-m-d');

                    $start_remind_after_this_date = Carbon::parse($warrantyExpiredDate)->subDays($warrantySetting->reminder_day)->format('Y-m-d');
                    $stop_remind_after_this_date = Carbon::parse($start_remind_after_this_date)->addDays($warrantySetting->reminder_day)->format('Y-m-d');

                    if (Carbon::now() > $start_remind_after_this_date && Carbon::now() < $stop_remind_after_this_date) {
                        
                        $dayDifference = Carbon::parse($warrantyExpiredDate)->diffInDays(Carbon::now()->format('Y-m-d'));
                        $dayDifference = (int) abs($dayDifference);

                        $amenities_reminder[] = [
                            'unit_id' => $unit->id,
                            'unit' => $unit->unit_number,
                            'amenity_id' => $privateClaimItemSetting->id,
                            'amenity_name' => app()->getLocale() === 'th' ? $privateClaimItemSetting?->privateClaimItem?->name_th : $privateClaimItemSetting?->privateClaimItem?->name,
                            'balance_day' => $dayDifference,
                        ];
                    }
                }
            }

            return $amenities_reminder ?? [];
        }
    }

    public function store(CreateWarrantyReminderFeedbackRequest $request)
    {
        $warranty_reminder = WarrantyReminder::create([
            'unit_id' => $request->unit_id,
            'user_id' => $request->user_id,
            'amenity_id' => $request->amenity_id,
            'stop_remind_at' => $request->stop_remind_at,
        ]);

        if (! $warranty_reminder) {
            throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Submission failed!');
        }

        return $warranty_reminder;
    }
}
