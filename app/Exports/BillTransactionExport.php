<?php

namespace App\Exports;

use App\Enums\Bill\PaymentMode;
use App\Enums\Bill\TransactionStatus;
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

class BillTransactionExport extends DefaultValueBinder implements FromCollection, ShouldAutoSize, WithCustomValueBinder, WithHeadings, WithStyles
{
    protected Collection $records;

    public function __construct(Collection $records)
    {
        $this->records = $records;
    }

    /**
     * The columns this report always exports, in this order. The file no longer
     * follows the table's visible columns: those included columns the report has
     * no data for (invoice.unit.residence.name, invoice.unit.home_id), which left
     * blank columns in the file.
     */
    public const COLUMN_LABELS = [
        'ref_no' => 'Receipt No (เลขที่ใบเสร็จ)',
        'invoice.invoice_no' => 'Invoice No (เลขที่ใบแจ้งค่าใช้จ่าย)',
        'invoice.bill_no' => 'Bill No (เลขที่อ้างอิง)',
        'invoice.unit.unit_number' => 'Unit Number (บ้านเลขที่)',
        'paid_amount' => 'Amount Paid (THB) (จำนวนเงิน (บาท))',
        'paymentMethod.payment_mode' => 'Payment Method (วิธีการชำระเงิน)',
        'payer_name' => 'Payer Name (ชื่อผู้ชำระค่าใช้จ่าย)',
        'transaction_datetime' => 'Payment Date (วันที่ชำระ)',
        'transaction_time' => 'Payment Time (เวลาชำระเงิน)',
        'status' => 'Status (สถานะ)',
        'reviewedBy.name' => 'Checked By (ตรวจสอบโดย)',
        'remark' => 'Remark (หมายเหตุ)',
        'invoice.due_date' => 'Due Date (วันครบกำหนดชำระ)',
        'created_at' => 'Created At Date (สร้างเมื่อวันที่)',
        'created_at_time' => 'Created At Time (สร้างเมื่อเวลา)',
        'updated_at' => 'Checked At (DateTime) (ตรวจสอบเมื่อ (วันเวลา))',
    ];

    public function collection()
    {
        // Check if $records is indeed a Collection and has items
        if ($this->records instanceof Collection) {
            return $this->records->map(function ($record) {
                $data = [
                    'ref_no' => $record?->ref_no,
                    'invoice.invoice_no' => $record?->invoice?->invoice_no,
                    'invoice.bill_no' => $record?->invoice?->bill_no,
                    'invoice.unit.unit_number' => $record?->invoice?->unit?->unit_number ?? '-',
                    'paid_amount' => $record->paid_amount,
                    'paymentMethod.payment_mode' => PaymentMode::tryFrom($record->payment_id)?->getLabel() ?? $record?->paymentMethod?->payment_mode,
                    'payer_name' => $record?->payer_name,
                    'transaction_datetime' => date('d-M-Y', strtotime($record->transaction_datetime)),
                    'transaction_time' => date('H:i:s', strtotime($record->transaction_datetime)),
                    'status' => TransactionStatus::tryFrom($record->status)?->getLabel() ?? '-',
                    'reviewedBy.name' => $record?->reviewedBy?->name,
                    'remark' => $record->remark,
                    'invoice.due_date' => $record?->invoice?->due_date,
                    'updated_at' => $record->status != TransactionStatus::PENDING->value ? $record->updated_at->format('d-M-Y H:i:s') : '-',
                    'created_at' => $record->created_at->format('d-M-Y'),
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
