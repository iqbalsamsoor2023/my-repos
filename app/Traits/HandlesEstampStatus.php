<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use App\Enums\Visitor\VisitingArrangementStatus;
use App\Enums\Visitor\EstampByType;
use App\Enums\Visitor\LogEstampStatus;

trait HandlesEstampStatus
{
    /**
     * Compute estamp-related state for a VisitorLog record.
     * Returns array with keys: hasArrangements, hasNullStatus, estampStatus
     *
     * @param mixed $record VisitorLog model instance (can be relation-loaded or not)
     * @return array
     */
    public static function computeEstampState($record): array
    {
        // Determine arrangements (prefer relation when present and non-empty)
        if (method_exists($record, 'relationLoaded') && $record->relationLoaded('visitingArrangements') && $record->visitingArrangements->isNotEmpty()) {
            $arrangements = $record->visitingArrangements;
        } else {
            $arrangements = DB::table('visiting_arrangements')
                ->where('visitor_log_id', $record->id)
                ->whereNull('deleted_at')
                ->get(['unit_id', 'status', 'feedback_remark', 'estamp_by_type']);

            if ($arrangements->isEmpty()) {
                $arrangements = DB::table('visiting_arrangements_archive')
                    ->where('visitor_log_id', $record->id)
                    ->whereNull('deleted_at')
                    ->get(['unit_id', 'status', 'feedback_remark', 'estamp_by_type']);
            }
        }

        $hasArrangements = $arrangements->isNotEmpty();

        // detect any null/empty status (used to show E-stamp button)
        $hasNullStatus = $arrangements->contains(function ($a) {
            return is_null($a->status) || $a->status === '';
        });

        // compute estamp status string
        if (! $hasArrangements) {
            $estampStatus = LogEstampStatus::PENDING->name;
        } else {
            $statuses = $arrangements->pluck('status')->filter()->unique()->values()->all();

            if (in_array(VisitingArrangementStatus::NOT_MY_VISITOR->value, $statuses) && ! in_array(VisitingArrangementStatus::MY_VISITOR->value, $statuses)) {
                $estampStatus = VisitingArrangementStatus::NOT_MY_VISITOR->name;
            } elseif (in_array(VisitingArrangementStatus::STAMP_BY_PM->value, $statuses)) {
                $estampStatus = VisitingArrangementStatus::STAMP_BY_PM->name;
            } else {
                $hasStampedByPM = false;
                $hasStampedByResident = false;
                $hasCancelBySG = false;
                $hasFeedback = false;

                foreach ($arrangements as $a) {
                    $feedback = null;
                    if (property_exists($a, 'feedback') && ! is_null($a->feedback)) {
                        $feedback = $a->feedback;
                    }
                    if (property_exists($a, 'feedback_remark') && ! is_null($a->feedback_remark) && $a->feedback_remark !== '') {
                        $feedback = $a->feedback_remark;
                    }
                    if (property_exists($a, 'feedback_channel') && ! is_null($a->feedback_channel)) {
                        $feedback = $a->feedback_channel;
                    }

                    if (! is_null($feedback) && $feedback !== '') {
                        $hasFeedback = true;
                    }

                    if (property_exists($a, 'status') && (int) $a->status === VisitingArrangementStatus::STAMP_BY_PM->value) {
                        $hasStampedByPM = true;
                    }
                    if (property_exists($a, 'status') && (int) $a->status === VisitingArrangementStatus::MY_VISITOR->value) {
                        if (! is_null($feedback) && $feedback !== '') {
                            $hasStampedByResident = true;
                        }
                    }
                    if (property_exists($a, 'status') && (int) $a->status === VisitingArrangementStatus::CANCEL_BY_SG->value) {
                        if (! is_null($feedback) && $feedback !== '') {
                            $hasCancelBySG = true;
                        }
                    }
                }

                if ($hasStampedByPM) {
                    $estampStatus = LogEstampStatus::STAMP_BY_PM->name;
                } elseif ($hasStampedByResident) {
                    $estampStatus = LogEstampStatus::ESTAMP->name;
                } elseif ($hasCancelBySG && $hasFeedback) {
                    $estampStatus = LogEstampStatus::CANCEL_BY_SG->name;
                } else {
                    $estampStatus = LogEstampStatus::PENDING->name;
                }
            }
        }

        return [
            'hasArrangements' => $hasArrangements,
            'hasNullStatus' => $hasNullStatus,
            'estampStatus' => $estampStatus,
        ];
    }
}
