<?php

namespace App\Exports;

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

class PetExport extends DefaultValueBinder implements FromCollection, ShouldAutoSize, WithCustomValueBinder, WithHeadings, WithStyles
{
    protected Collection $records;

    protected array $columns;

    protected array $columnLabels = [
        'unit.residence.name' => 'MooBan (โครงการ)',
        'unit.unit_number' => 'Unit Number (บ้านเลขที่)',
        'user.name' => 'Owner (เจ้าของ)',
        'type' => 'Type (ประเภท)',
        'breed' => 'Breed (สายพันธุ์)',
        'pet_age' => 'Age (อายุ)',
        'created_at_date' => 'Created At Date (สร้างเมื่อวันที่)',
        'created_at_time' => 'Created At Time (สร้างเมื่อเวลา)',
    ];

    public function __construct(Collection $records, array $columns)
    {
        $this->records = $records;
        $this->columns = $columns;
    }

    public function collection()
    {
        // Check if $records is indeed a Collection and has items
        return $this->records->map(function ($record) {
            $row = [];

            foreach ($this->columns as $column) {
                // Handle custom computed fields
                switch ($column) {
                    case 'unit.residence.name':
                        $row[$column] = $record?->unit?->residence?->name.' ('.$record?->unit?->residence?->name_th.')';
                        break;

                    case 'created_at_date':
                        $row[$column] = $record->created_at?->format('d-M-Y');
                        break;

                    case 'created_at_time':
                        $row[$column] = $record->created_at?->format('H:i:s');
                        break;

                    default:
                        // Use Laravel's data_get to access nested fields
                        $row[$column] = data_get($record, $column);
                        break;
                }
            }

            return $row;
        });
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
