<?php

namespace App\Exports;

use App\Models\AutoSendReport;
use App\Models\IncidentReport;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;

class IncidentASRExport implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings
{
    use RegistersEventListeners;

    protected AutoSendReport $autoSendReport;

    protected Carbon $datetime;

    public function __construct(AutoSendReport $autoSendReport, Carbon $datetime)
    {
        $this->autoSendReport = $autoSendReport;
        $this->datetime = $datetime;
    }

    public function headings(): array
    {
        return [
            'ID',
            'MooBaan',
            'Security Guard Staff Name',
            'House Unit',
            'Title',
            'Description',
            'Images',
            'Created At',
        ];
    }

    public function collection()
    {
        $residence = $this->autoSendReport->residence;
        $hour = $this->autoSendReport->hour;
        $time = $this->datetime;

        $subtime = $time->copy()->sub("$hour hours");

        $housePatrols = IncidentReport::with('unit', 'unit.residence', 'createdBy')->whereHas('unit', function ($query) use ($residence) {
            $query->whereHas('residence', function ($query) use ($residence) {
                $query->where('id', $residence->id);
            });
        })
            ->setModel(new IncidentReport) // Set model connection back to "sgoc"
            ->where('created_at', '>=', $subtime)
            ->latest()
            ->get();

        $collection = collect();

        foreach ($housePatrols as $housePatrol) {
            $imageUrl = $housePatrol->image_url;

            $row = collect([
                'id' => $housePatrol->id,
                'mooban' => $residence->name,
                'security_guard_staff_name' => $housePatrol->createdBy->name,
                'house_unit' => $housePatrol->unit->unit_number ?? null,
                'title' => $housePatrol->title,
                'description' => $housePatrol->description,
                'image' => $imageUrl,
                'created_at' => $housePatrol->created_at,
            ]);

            $collection->push($row);
        }

        return $collection;
    }
}
