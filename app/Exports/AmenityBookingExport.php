<?php

namespace App\Exports;

use App\Enums\FacilityAndAmenity\AmenityBookingStatusEnum;
use App\Models\ResidenceAmenity;
use Carbon\Carbon;
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

class AmenityBookingExport extends DefaultValueBinder implements FromCollection, ShouldAutoSize, WithCustomValueBinder, WithHeadings, WithStyles
{
    protected Collection $records;

    protected array $columns;

    public function __construct(Collection $records, array $columns)
    {
        $this->records = $records;
        $this->columns = $columns;
    }

    protected array $columnLabels = [
        'ref_no' => 'Reference No (โครงการ)',
        'unit.residence.name' => 'MooBan (โครงการ)',
        'unit.unit_number' => 'Unit Number (บ้านเลขที่)',
        'amenity_bookingable_type' => 'Amenity (สิ่งอำนวยความสะดวก)',
        'user.name' => 'Resident (ลูกบ้าน)',
        'start_at' => 'Booking Date',
        'booking_time' => 'Booking Time',
        'status' => 'Status (สถานะ)',
        'created_at_date' => 'Created At Date (สร้างเมื่อวันที่)',
        'created_at_time' => 'Created At Time (สร้างเมื่อเวลา)',
    ];

    protected function reorderColumns(array $columns): array
    {
        // Remove 'unit.residence.name' if it exists anywhere
        $columns = array_filter($columns, fn ($col) => $col !== 'unit.residence.name');

        // Find index of 'ref_no'
        $refNoIndex = array_search('ref_no', $columns);

        if ($refNoIndex !== false) {
            // Insert 'unit.residence.name' right after 'ref_no'
            array_splice($columns, $refNoIndex + 1, 0, ['unit.residence.name']);
        } else {
            // If no 'ref_no' found, add at start
            array_unshift($columns, 'unit.residence.name');
        }

        return $columns;
    }

    public function collection()
    {
        if ($this->records instanceof Collection) {
            // Apply reordered columns
            $columns = $this->reorderColumns($this->columns);

            return $this->records->map(function ($record) use ($columns) {
                // Build the full dataset with labels as keys

                $record->start_at = (new Carbon($record->start_at))->format('H:i');
                $record->end_at = (new Carbon($record->end_at))->format('H:i');

                $data = [
                    'ref_no' => $record?->ref_no,
                    'unit.residence.name' => $record?->unit?->residence?->name.' ('.$record?->unit?->residence?->name_th.')',
                    'unit.unit_number' => $record?->unit?->unit_number ?? '-',
                    'amenity_bookingable_type' => $record->amenity_bookable_type === ResidenceAmenity::class
                        ? $record?->amenityBookable?->facilityAndAmenity?->name.' ('.$record?->amenityBookable?->facilityAndAmenity?->name_in_thai.')'
                        : $record->amenityBookable?->name.' ('.$record->amenityBookable?->name_in_thai.')',
                    'user.name' => $record?->user?->name,
                    'start_at' => date('d-M-y', strtotime($record->start_at)),
                    'booking_time' => $record->start_at.'-'.$record->end_at,
                    'status' => AmenityBookingStatusEnum::tryFrom($record->status)?->getLabel() ?? '-',
                    'created_at_date' => $record->created_at->format('d-M-Y'),
                    'created_at_time' => $record->created_at->format('H:i:s'),
                ];

                // Include only keys in reordered columns, fill others with null
                $filteredData = array_intersect_key($data, array_flip($columns));
                $finalData = array_replace(array_fill_keys($columns, null), $filteredData);

                return collect($finalData);
            });
        }

        return collect();
    }

    public function headings(): array
    {
        $columns = $this->reorderColumns($this->columns);

        return array_map(fn ($col) => $this->columnLabels[$col] ?? $col, $columns);
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
