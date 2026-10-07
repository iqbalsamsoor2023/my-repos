<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use App\Models\AutoSendReport;
use App\Models\VisitorLog;
use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class VisitorsASRExport implements FromCollection, WithHeadings
{
    protected AutoSendReport $autoSendReport;

    protected Carbon $datetime;

    public function __construct(AutoSendReport $autoSendReport, ?Carbon $datetime = null)
    {
        $this->autoSendReport = $autoSendReport;
        $this->datetime = $datetime;
    }

    public function headings(): array
    {
        return [
            'Visitor No',
            'MooBaan',
            'Unit',
            'Method',
            'Temperature',
            'Company Name',
            'Follower',
            'Type',
            'Vehicle Type',
            'Vehicle Number',
            'Name',
            'Purpose of Visit',
            'Thai License ID',
            'Duration Type',
            'Duration',
            'Arrive At',
            'Depart At',
            'Visitor Card',
            'Create At',
            'Status',
            'Stamp By',
        ];
    }

    /**
     * @return Collection
     */
    public function collection()
    {
        $residence = $this->autoSendReport->residence;
        $hour = $this->autoSendReport->hour;

        if ($this->datetime == null) {
            $time = new Carbon($this->autoSendReport->time);
        } else {
            $time = $this->datetime;
        }

        $subtime = $time->copy()->sub("$hour hours");

        $visitorLogs = VisitorLog::with('visitor', 'visitingArrangements', 'visitingArrangements.residence', 'visitingArrangements.unit')->whereHas('visitingArrangements', function (Builder $query) use ($residence) {
            $query->where('residence_id', $residence->id);
        })
            ->where('created_at', '>=', $subtime)
            ->with('visitor')
            ->latest('id')
            ->with('visitor')
            ->get();

        $collection = collect();
        foreach ($visitorLogs as $key => $visitorLog) {
            $row = collect([
                'visitor_no' => data_get($visitorLog, 'visitor_generated_no') ?? '-',
                'mooban' => implode(', ', array_unique(data_get($visitorLog, 'visitingArrangements.*.residence.name'))),
                'unit' => implode(', ', array_unique(data_get($visitorLog, 'visitingArrangements.*.unit.unit_number'))),
                'method' => $this->getVisitorLogMethod($visitorLog),
                'temperature' => data_get($visitorLog, 'temperature'),
                'company_name' => data_get($visitorLog, 'company_name'),
                'follower' => data_get($visitorLog, 'passenger_count'),
                'type' => $visitorLog->arrival_types,
                'vehicle_type' => $visitorLog->vehicle_types,
                'vehicle_number' => data_get($visitorLog, 'vehicle_plate_no'),
                'name' => data_get($visitorLog, 'visitor.name'),
                'purpose_of_visit' => data_get($visitorLog, 'visitor_purpose'),
                'thai_license_id' => data_get($visitorLog, 'visitor.id_number'),
                'duration_type' => $this->getDurationType($visitorLog),
                'duration' => $this->getDuration($visitorLog),
                'arrive_at' => $visitorLog->arrival_time,
                'depart_at' => $visitorLog->leave_time,
                'visitor_card' => $visitorLog->visitor_card_id,
                'create_at' => $visitorLog->created_at,
                'status' => $visitorLog->estampStatus,
                'stamp_by' => $visitorLog->stamp_name,
            ]);
            $collection->push($row);
        }

        return $collection;
    }

    private function getVisitorLogMethod(VisitorLog $visitorLog): string
    {
        if ($visitorLog->is_pre_register) {
            return __('preregister');
        }

        return __('register');
    }

    private function getDurationType(VisitorLog $visitorLog): string
    {
        $message = '-';

        if ($visitorLog->is_pre_register) {
            $message = $visitorLog->preregisterVisitor->is_multiple_entry ? 'Limited Time' : 'One Time';
        }

        return $message;
    }

    private function getDuration(VisitorLog $visitorLog): string
    {
        $message = '-';
        $durationStart = '';
        $durationEnd = '';

        if ($visitorLog->is_pre_register) {
            $preRegisterVisitor = $visitorLog->preregisterVisitor;
            if ($preRegisterVisitor) {
                $durationStart = $preRegisterVisitor->validity_start_date;

                if ($preRegisterVisitor->is_multiple_entry == true) {
                    $durationEnd = $preRegisterVisitor->validity_end_date;
                }
            }
        }

        if (! empty($durationStart) || ! empty($durationEnd)) {
            $message = "$durationStart - $durationEnd";
        }

        return $message;
    }
}
