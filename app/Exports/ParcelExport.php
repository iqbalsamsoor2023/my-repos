<?php

namespace App\Exports;

use App\Enums\Parcel\ParcelStatus;
use Carbon\Carbon;
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

class ParcelExport extends DefaultValueBinder implements FromCollection, ShouldAutoSize, WithCustomValueBinder, WithHeadings, WithStyles
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
        'parcel_generated_no' => 'Parcel ID (หมายเลขรายการ)',
        'parcel_receiver' => 'Receiver (ผู้รับ)',
        'description' => 'Description (รายละเอียด)',
        'courier.name' => 'Courier Company (บริษัทขนส่ง)',
        'tracking_no' => 'Tracking Number (หมายเลขติดตาม)',
        'status' => 'Status (สถานะ)',
        'created_at' => 'Created At Date (สร้างเมื่อวันที่)',
        'created_at_time' => 'Created At Time (สร้างเมื่อเวลา)',
        'pickup_time' => 'Pickup Time (รับเมื่อ)',
        'updated_at' => 'Updated At',
    ];

    public function collection()
    {
        if ($this->records instanceof Collection) {
            return $this->records->map(function ($record) {
                // Build the full dataset with labels as keys
                $data = [
                    'unit.residence.name' => $record?->unit?->residence?->name.' ('.$record?->unit?->residence?->name_th.')',
                    'unit.unit_number' => $record?->unit?->unit_number ?? '-',
                    'parcel_generated_no' => $record->parcel_generated_no,
                    'parcel_receiver' => 'To: '.($record->receiver_name ?? $record->receiver->name)."\n".
                        'Receiver: '.($record->pickup_person_name ?? '-')."\n".
                        'Phone Number: '.($record->pickup_person_contact_no ?? '-'),
                    'description' => $record->description,
                    'courier.name' => $record?->courier?->name,
                    'tracking_no' => $record->tracking_no,
                    'status' => ParcelStatus::tryFrom($record->status)?->getLabel() ?? '-',
                    'created_at' => $record->created_at->format('d-M-Y'),
                    'created_at_time' => $record->created_at->format('H:i:s'),
                    'pickup_time' => $record->pickup_time ? Carbon::parse($record->pickup_time)->format('d-M-Y H:i:s') : '',
                    'updated_at' => $record->updated_at->format('d-M-Y H:i:s'),
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

        // Dynamically enable wrapping for specific columns
        $headings = $this->headings();

        // List of labels that need text wrapping
        $wrapLabels = [
            'Receiver (ผู้รับ)',
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
