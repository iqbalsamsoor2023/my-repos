<?php

namespace App\Exports;

use Carbon\Carbon;
use App\Enums\Visitor\ArrivalType;
use App\Enums\Visitor\VehicleType;
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

class PrebookVisitorExport extends DefaultValueBinder implements FromCollection, ShouldAutoSize, WithCustomValueBinder, WithHeadings, WithStyles
{
    protected Collection $records;

    protected array $columns;

    public function __construct(Collection $records, array $columns)
    {
        $this->records = $records;
        $this->columns = $columns;
    }

    protected array $columnLabels = [
        'unit.residence.name' => 'MooBan (โครงการ)',
        'unit.unit_number' => 'Unit Number (บ้านเลขที่)',
        'visitor.name' => 'Visitor Name',
        'visitor_purpose' => 'Purpose of Visit (วัตถุประสงค์ของการเยี่ยมชม)',
        'arrival_type' => 'Arrival Type (วิธีการเข้ามา)',
        'vehicle_type' => 'Vehicle Type (ชนิดยานพาหนะ)',
        'vehicle_plate_no' => 'Vehicle Plate Number (ทะเบียนรถ)',
        'validity_start_date' => 'Validity Start Date (วันที่เริ่มใช้งาน)',
        'validity_end_date' => 'Validity End Date (วันที่สิ้นสุด)',
        'is_multiple_entry' => 'Is Multiple Entry (เข้าออกหลายครั้ง)',
        'is_qr_code_expired' => 'Is Qr Code Expired (คิวอาร์โค้ดหมดอายุ)',
    ];

    public function collection()
    {
        if ($this->records instanceof Collection) {
            return $this->records->map(function ($record) {
                // Build the full dataset with labels as keys
                $data = [
                    'unit.residence.name' => $record?->unit?->residence?->name.' ('.$record?->unit?->residence?->name_th.')',
                    'unit.unit_number' => $record?->unit?->unit_number,
                    'visitor.name' => $record?->visitor?->name,
                    'visitor_purpose' => $record?->visitor_purpose,
                    'arrival_type' => ArrivalType::tryFrom($record?->arrival_type)?->getLabel() ?? '-',
                    'vehicle_type' => VehicleType::tryFrom($record?->arrival_type)?->getLabel() ?? '-',
                    'vehicle_plate_no' => $record?->vehicle_plate_no,
                    'validity_start_date' => Carbon::parse($record?->validity_start_date)->format('d-M-Y H:i:s'),
                    'validity_end_date' => Carbon::parse($record?->validity_end_date)->format('d-M-Y H:i:s'),
                    'is_multiple_entry' => $record->is_multiple_entry == 1 ? 'yes' : 'no',
                    'is_qr_code_expired' => $record->is_qr_code_expired == 1 ? 'yes' : 'no',
                ];

                return collect($data)->only($this->columns);
            });
        }

        return collect();
    }

    public function headings(): array
    {
        return array_map(function ($column) {
            return $this->columnLabels[$column] ?? $column; // fallback to raw key if no label
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
