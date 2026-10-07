<?php

namespace App\Exports;

use App\Enums\FacilityBooking\FacilityBookingStatus;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FacilityBookingExport extends DefaultValueBinder implements FromCollection, ShouldAutoSize, WithCustomValueBinder, WithHeadings, WithStyles
{
    protected Collection $records;

    protected array $columns;

    public function __construct(Collection $records, array $columns)
    {
        $this->records = $records;
        $this->columns = $columns;

        // Find the indexes of the columns you want to swap
        $unitIndex = array_search('unit.unit_number', $this->columns);
        $facilityIndex = array_search('facility.name', $this->columns);

        // Check if both indexes exist
        if ($unitIndex !== false && $facilityIndex !== false) {
            // Remove them from their original positions
            $unit = array_splice($this->columns, $unitIndex, 1);
            $facility = array_splice($this->columns, $facilityIndex, 1);

            // Insert unit.unit_number after facility.residence.name and before facility.name
            array_splice($this->columns, 2, 0, $unit);
            array_splice($this->columns, 3, 0, $facility);
        }
    }

    protected array $columnLabels = [
        'ref_no' => 'Reference No (โครงการ)',
        'facility.residence.name' => 'MooBan (โครงการ)',
        'unit.unit_number' => 'Unit Number (บ้านเลขที่)',
        'facility.name' => 'Facility (สิ่งอำนวยความสะดวก)',
        'user.name' => 'Resident (ลูกบ้าน)',
        'start_at' => 'Booking Date',
        'booking_time' => 'Booking Time',
        'status' => 'Status (สถานะ)',
        'created_at' => 'Created At Date (สร้างเมื่อวันที่)',
        'created_at_time' => 'Created At Time (สร้างเมื่อเวลา)',
    ];

    public function collection()
    {
        if ($this->records instanceof Collection) {
            return $this->records->map(function ($record) {
                // Build the full dataset with labels as keys
                $data = [
                    'ref_no' => $record?->ref_no,
                    'facility.residence.name' => $record?->facility?->residence?->name.' ('.$record?->facility?->residence?->name_th.')',
                    'unit.unit_number' => $record?->unit?->unit_number ?? '-',
                    'facility.name' => $record?->facility?->name,
                    'user.name' => $record?->user?->name,
                    'start_at' => date('d-M-y', strtotime($record->start_at)),
                    'booking_time' => $record->booking_time,
                    'status' => FacilityBookingStatus::tryFrom($record->status)?->getLabel() ?? '-',
                    'created_at' => $record->created_at->format('d-M-Y'),
                    'created_at_time' => $record->created_at->format('H:i:s'),
                ];

                return collect(array_replace(array_fill_keys($this->columns, null), $data));
            });
        }

        return collect();
    }

    public function headings(): array
    {
        return array_map(function ($column) {
            return $this->columnLabels[$column] ?? $column;
        }, $this->columns);
    }

    public function styles(Worksheet $sheet)
    {
        // Applying style to the header row
        $headerRow = 1;

        // Set background color to #003060 (dark blue)
        $sheet->getStyle('A'.$headerRow.':'.$sheet->getHighestColumn().$headerRow)
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setRGB('003060');

        // Set text color to white for header
        $sheet->getStyle('A'.$headerRow.':'.$sheet->getHighestColumn().$headerRow)
            ->getFont()
            ->getColor()
            ->setRGB('FFFFFF');

        // Set bold and center alignment for the headers
        $sheet->getStyle('A'.$headerRow.':'.$sheet->getHighestColumn().$headerRow)
            ->getFont()
            ->setBold(true);

        // Set center alignment for all header cells
        $sheet->getStyle('A'.$headerRow.':'.$sheet->getHighestColumn().$headerRow)
            ->getAlignment()
            ->setHorizontal('center')
            ->setVertical('center');
    }

    public function bindValue(Cell $cell, $value)
    {
        if (is_numeric($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }
}
