<?php

namespace App\Exports;

use App\Enums\Vehicle\VehicleType;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class VehicleExport extends DefaultValueBinder implements FromCollection, ShouldAutoSize, WithCustomValueBinder, WithHeadings, WithStyles
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
        'resident' => 'Resident (ลูกบ้าน)',
        'vehicleModel.type' => 'Type (ประเภท)',
        'Vehicle Details' => 'Vehicle Details (รายละเอียดรถ)',
        'province.name_in_english' => 'Province (จังหวัด)',
        'vehicle_age' => 'Vehicle Age (อายุของยานพาหนะ)',
        'is_access_card' => 'Is Access Card (บัตรผ่าน)',
        'is_car_sticker' => 'Is Car Sticker (สติกเกอร์รถ)',
        'created_at' => 'Created At Date (สร้างเมื่อวันที่)',
        'created_at_time' => 'Created At Time (สร้างเมื่อเวลา)',
    ];

    public function collection()
    {
        if (! ($this->records instanceof Collection)) {
            return collect();
        }

        return $this->records->map(function ($record) {
            $rawData = [
                'unit.residence.name' => $record?->unit?->residence?->name.' ('.$record?->unit?->residence?->name_th.')',
                'unit.unit_number' => $record?->unit?->unit_number,
                'resident' => 'Name: '.($record->user?->name ?? '-')."\n".
                              'Email: '.($record->user?->email ?? '-')."\n".
                              'Phone Number: '.($record->user?->phone_no ?? '-'),
                'vehicleModel.type' => VehicleType::tryFrom($record?->vehicleModel?->type)?->category() ?? 'Unknown',
                'Vehicle Details' => 'Brand: '.($record?->vehicleModel?->vehicleBrand?->name ?? '-')."\n".
                                     'Model: '.($record?->vehicleModel?->name ?? '-')."\n".
                                     'Plate Number: '.($record?->plate_number ?? '-'),
                'province.name_in_english' => $record?->province?->name_in_english,
                'vehicle_age' => $record->vehicle_age,
                'is_access_card' => $record->is_access_card == 1 ? 'yes' : 'no',
                'is_car_sticker' => $record->is_car_sticker == 1 ? 'yes' : 'no',
                'created_at' => $record->created_at->format('d-M-Y'),
                'created_at_time' => $record->created_at->format('H:i:s'),
            ];

            return collect($this->columns)
                ->mapWithKeys(function ($key) use ($rawData) {
                    $label = $this->columnLabels[$key] ?? $key;

                    return [$label => $rawData[$key] ?? '-'];
                });
        });
    }

    public function headings(): array
    {
        return array_map(function ($key) {
            return $this->columnLabels[$key] ?? $key;
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

        // Dynamically enable wrapping for specific columns
        $headings = $this->headings();

        // List of labels that need text wrapping
        $wrapLabels = [
            'Resident (ลูกบ้าน)',
            'Vehicle Details (รายละเอียดรถ)',
        ];

        foreach ($wrapLabels as $label) {
            $index = array_search($label, $headings);
            if ($index !== false) {
                $columnLetter = Coordinate::stringFromColumnIndex($index + 1); // 1-based index
                $sheet->getStyle($columnLetter.'2:'.$columnLetter.$sheet->getHighestRow())
                    ->getAlignment()
                    ->setWrapText(true);
            }
        }
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
