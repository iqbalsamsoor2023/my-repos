<?php

namespace App\Helpers;

use App\Models\SupportTicket;
use App\Models\Unit;
use Carbon\Carbon;

class CaseIdGenerator
{
    // format 03019-221011-C1 <Mooban-date-C+running number for the day>
    public static function generate(SupportTicket $supportTicket): string
    {
        $date = Carbon::now();
        $date = $date->format('ymd');
        $supportTicketToday = SupportTicket::whereDate('created_at', $date)
            ->where('platform_identifier', $supportTicket->platform_identifier)
            ->count();

        $unit = Unit::whereId($supportTicket->unit_id)->first();
        $caseId = str_pad($unit->residence_id, 5, '0', STR_PAD_LEFT);
        $caseId .= '-';
        $caseId .= str_pad($date, 6, '0', STR_PAD_LEFT);
        $caseId .= '-C';
        $caseId .= $supportTicketToday;

        return $caseId;
    }
}
