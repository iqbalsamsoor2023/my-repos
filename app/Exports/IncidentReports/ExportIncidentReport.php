<?php

namespace App\Exports\IncidentReports;

use App\Models\Unit;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ExportIncidentReport implements FromCollection, WithHeadings
{
    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return collect($this->indexMap($this->data['data']));
    }

    public function headings(): array
    {
        return ['ID', 'Residence', 'Unit', 'Security Guard Staff', 'Title', 'Description', 'Image', 'Created at (date)', 'Created at (time)'];
    }

    /**
     * Transformer for DT data attributes
     */
    private function indexMap(array $data = []): array
    {
        $modelMapping = [];
        foreach ($data as $key => $data) {
            $unit = Unit::whereId($data['mmb_unit_id'])->first();
            $modelMapping[] = [
                'id' => $key + 1,
                'residence' => isset($unit->residence) ? $unit->residence->name : '-',
                'unit' => $unit->unit_number,
                'staff' => $data['createdBy']['name'] ?? '-',
                'title' => $data['title'],
                'description' => $data['description'],
                'image' => $data['image_url'],
                'created_at_date' => Carbon::parse($data['created_at'])->format('M d, Y'),
                'created_at_time' => Carbon::parse($data['created_at'])->format('H:i'),
            ];
        }

        return $modelMapping;
    }
}
