<?php

namespace App\Http\Resources\Maintenance;

use App\Models\ResidenceAmenity;
use App\Models\ResidenceAmenityOption;
use App\Models\Unit;
use Illuminate\Http\Resources\Json\JsonResource;

class MaintenanceResource extends JsonResource
{
    public function toArray($request)
    {
        $lang = strtolower($request->header('Accept-Language'));
        $isThai = $lang === 'th';

        $maintainable = $this->maintainable;
        $residence = null;
        $unit_id = null;
        $residence_id = null;
        $facility_or_amenity = null;
        $claimable_title_id = null;
        $claimable_title = null;
        $reportedBy = data_get($this->reportedBy, 'name');

        switch ($this->maintainable_type) {
            case Unit::class:
                $unit = Unit::with('residence')->findOrFail($maintainable->id);
                $residence = $unit->residence;
                $residence_id = $residence->name;
                $unit_id = $unit->unit_number;

                $claimable_title_id = $this->claimable_item_details['id'] ?? null;
                $title = $this->claimable_item_details['amenity_name'] ?? null;
                if ($title === 'Others' && !empty($this->miscellaneous)) {
                    $claimable_title = 'Others - ' . $this->miscellaneous;
                } else {
                    $claimable_title = $title;
                }
                break;

            case ResidenceAmenity::class:
                $item = ResidenceAmenity::with('residence')->findOrFail($maintainable->id);
                $residence = $item->residence;
                $residence_id = $residence->id;
                $facility_or_amenity = $isThai
                    ? $maintainable?->facilityAndAmenity?->name_in_thai
                    : $maintainable?->facilityAndAmenity?->name;

                $details = (array) ($this->claimable_item_details ?? []);
                $claimable_title_id = $details['id'] ?? null;
                $claimable_title = $isThai
                    ? ($details['claimable_title_name_th'] ?? null)
                    : ($details['claimable_title_name'] ?? null);
                break;

            case ResidenceAmenityOption::class:
                $item = ResidenceAmenityOption::with('residenceAmenity.residence')->findOrFail($maintainable->id);
                $residence = $item->residenceAmenity?->residence;
                $residence_id = $residence?->id;
                $facility_or_amenity = $isThai
                    ? $maintainable?->residenceAmenity?->facilityAndAmenity?->name_in_thai
                    : $maintainable?->residenceAmenity?->facilityAndAmenity?->name;

                $details = (array) ($this->claimable_item_details ?? []);
                $claimable_title_id = $details['id'] ?? null;
                $claimable_title = $isThai
                    ? ($details['claimable_title_name_th'] ?? null)
                    : ($details['claimable_title_name'] ?? null);
                break;
        }

        $has_verification = $residence?->warrantySetting->has_verification ?? 0;

        $progression = collect($this->maintenanceProgressions)->map(function ($progress) {
            return [
                'id' => $progress->id,
                'maintenance_id' => $progress->maintenance_id,
                'progress_description' => $progress->progress_description,
                'image_maintenance_progress' => $progress->image_url ?? null,
                'created_at' => $progress->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $progress->updated_at->format('Y-m-d H:i:s'),
            ];
        });

        $privateClaimCategory = null;
        $privateClaimItem = null;
        $privateClaimItemTitle = null;
       
        if ($this->privateClaimCategory) {
            $category = $this->privateClaimCategory;

            $privateClaimCategory = $category ? [
                'id' => $category->id,
                'name' => $isThai
                    ? ($category->name_th ?? null)
                    : ($category->name ?? null),
            ] : null;
        }

        if ($this->privateClaimItem) {
            $category = $this->privateClaimItem->privateClaimCategory;

            $privateClaimCategory = $category ? [
                'id' => $category->id,
                'name' => $isThai
                    ? ($category->name_th ?? null)
                    : ($category->name ?? null),
            ] : null;

            $privateClaimItem = [
                'id' => $this->privateClaimItem->id,
                'name' => $isThai
                    ? ($this->privateClaimItem->name_th ?? null)
                    : ($this->privateClaimItem->name ?? null),
            ];
        }

        if ($this->privateClaimItemTitle) {
            $privateClaimItemTitle = [
                'id' => $this->privateClaimItemTitle->id,
                'name' => $isThai
                    ? ($this->privateClaimItemTitle->option_name_th ?? null)
                    : ($this->privateClaimItemTitle->option_name ?? null),
            ];
        }

        $response = [
            'id' => $this->id,
            'maintainable_id' => $this->maintainable_id,
            'maintainable_type' => $this->maintainable_type,
            'maintainable_claim_number' => $this->maintainable_claim_number,
            'residence_id' => $residence_id,
            'unit' => $unit_id,
            'reported_by' => $reportedBy,
            'reported_by_phone_no' => data_get($this->reportedBy, 'phone_no'),
            'facility_or_amenity' => $facility_or_amenity,
            'private_claim_category' => $privateClaimCategory,
            'private_claim_item' => $privateClaimItem,
            'private_claim_item_title' => $privateClaimItemTitle,
            'other_private_claim_item' => $this->other_private_claim_item,
            // 'other_private_claim_category' => $this->other_private_claim_category,
            'claimable_title_id' => $claimable_title_id,
            'claimable_title_name' => $claimable_title,
            'appointment_datetime' => $this->appointment_datetime,
            'issue_description' => $this->issue_description,
            'image_urls' => explode(',', $this->image_url),
            'status' => $this->status,
            'status_label' => $this->status_label,
            'verify_private_claim' => $has_verification,
            'verify_public_claim' => $has_verification,
            'verification' => $this->is_verified,
            'maintenance_progress' => $progression,
            'image_maintenance_verification' => $this->image_verification_url,
            'verification_remark' => $this->verification_description,
            'completion_datetime' => $this->completion_datetime,
            'completed_image' => $this->image_completed_url,
            'completed_remark' => $this->completed_remark,
            'rating' => $this->rating,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];

        // to be removed after discontinuing v2 app support
        if (!$request->header('X-App-Version')) {
            $response['reported_by'] = [
                'name' => data_get($this->reportedBy, 'name'),
                'phone_no' => data_get($this->reportedBy, 'phone_no'),
            ];
            $response['maintainable'] = [
                'id' => $maintainable->id, // for pm talk router
                'unit_number' => is_null($unit_id) ? 'N/A' : $unit_id,
                'item' => $claimable_title, // for pm talk router
            ];

            $response['status'] = $this->status_label;
            if ($this->maintainable_type === Unit::class) {
                $response['private_claim_title'] = $claimable_title;
                $response['public_claim_title'] = null;
            } else {
                $response['private_claim_title'] = null;
                $response['public_claim_title'] = $claimable_title;
            }
            $response['miscellaneous'] = $this->miscellaneous;
        }

        return $response;
    }
}
