<?php

namespace App\Exports;

use App\Enums\Bill\BillStatus;
use App\Models\UnitUser;
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

class BillExport extends DefaultValueBinder implements FromCollection, ShouldAutoSize, WithCustomValueBinder, WithHeadings, WithStyles
{
    protected Collection $records;

    public function __construct(Collection $records)
    {
        $this->records = $records;
    }

    /**
     * The columns this report always exports, in this order. The file no longer
     * follows the table's visible columns: those included columns the report has
     * no data for (unit.residence.name, unit.home_id), which shifted every value
     * out of line with its heading.
     */
    public const COLUMN_LABELS = [
        'invoice_no' => 'Invoice No (เลขที่ใบแจ้งค่าใช้จ่าย)',
        'unit.unit_number' => 'Unit Number (บ้านเลขที่)',
        'total_amount' => 'Amount (THB) (จำนวนเงิน (บาท))',
        'amount_due' => 'Amount Due (THB) (จำนวนเงินที่ชำระ (บาท))',
        'pay_by' => 'Pay By (ชำระโดย)',
        'payers.user.name' => 'Payer Name (ชื่อผู้ชำระค่าใช้จ่าย)',
        'status' => 'Status (สถานะ)',
        'bill_date' => 'Bill Date (วันที่ออกบิล)',
        'due_date' => 'Due Date (วันครบกำหนดชำระ)',
        'remark' => 'Remark (หมายเหตุ)',
        'created_at_date' => 'Created At Date (สร้างเมื่อวันที่)',
        'created_at_time' => 'Created At Time (สร้างเมื่อเวลา)',
    ];

    public function collection()
    {
        // Check if $records is indeed a Collection and has items
        if ($this->records instanceof Collection) {
            return $this->records->map(function ($record) {
                $pay_by = '-';

                if (count($record->payers) > 1) {
                    $pay_by = __('app.all');
                } else {
                    foreach ($record->payers as $payer) {
                        $unitUser = UnitUser::where('user_id', $payer->payer_id)
                            ->where('unit_id', $record->payer_unit_id)
                            ->first();

                        if (is_null($unitUser) == false) {
                            if ($unitUser->is_main_owner == true) {
                                $pay_by = __('user.main_owner');
                            } elseif ($unitUser->is_main_tenant == true) {
                                $pay_by = __('user.main_tenant');
                            } else {
                                $pay_by = __('app.all');
                            }
                        }
                    }
                }
                // Payer Name
                $payers = [];

                foreach ($record->payers as $payer) {
                    $payers[] = ucfirst($payer->user->name ?? null);
                }

                $data = [
                    'invoice_no' => $record?->invoice_no,
                    'unit.unit_number' => $record?->unit?->unit_number ?? '-',
                    'total_amount' => $record?->total_amount,
                    'amount_due' => $record?->amount_due,
                    'pay_by' => $pay_by,
                    'payers.user.name' => implode(', ', $payers),
                    'status' => BillStatus::tryFrom($record->status)?->getLabel() ?? '-',
                    'bill_date' => $record->bill_date,
                    'due_date' => $record->due_date,
                    'remark' => $record->remark,
                    'created_at_date' => $record->created_at->format('d-M-Y'),
                    'created_at_time' => $record->created_at->format('H:i:s'),
                ];

                // Build the row in the same order as headings() so values line up with their labels
                return collect(array_keys(static::COLUMN_LABELS))
                    ->map(fn ($column) => $data[$column] ?? null);
            });
        }

        return collect(); // Fallback if records is not a collection
    }

    public function headings(): array
    {
        return array_values(static::COLUMN_LABELS);
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
