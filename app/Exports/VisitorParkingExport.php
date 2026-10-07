<?php

namespace App\Exports;

use App\Enums\Parking\DiscountType;
use App\Enums\Visitor\VehicleType;
use App\Models\Calculation;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class VisitorParkingExport extends DefaultValueBinder implements FromArray, ShouldAutoSize, WithCustomValueBinder, WithDrawings, WithHeadings, WithStyles
{
    protected Collection $records;

    protected array $columns;

    protected string $tempImageDir;

    protected array $columnLabels = [
        'visitorLog.visitor_generated_no' => 'Visitor No (หมายเลขรายการ)',
        'visitorLog.visitor.name' => 'Name (ชื่อ)',
        'visitorLog.vehicle_type' => 'Vehicle Type',
        'visitorLog.vehicle_plate_no' => 'Vehicle Plate Number',
        'unit_to_visit' => 'Unit to Visit (ติดต่อบ้านเลขที่)',
        'arrival_date' => 'Arrived At Date (มาถึงเมื่อ (วันที่))',
        'visitorLog.arrival_time' => 'Arrived At Time (มาถึงเมื่อ (เวลา))',
        'departure_date' => 'Departed At Date (ออกไปเมื่อ (วันที่))',
        'visitorLog.leave_time' => 'Departed At Time (ออกไปเมื่อ (เวลา))',
        'parking_hour' => 'Parking Hour (เวลาจอดรถ)',
        'is_free_parking' => 'Is Free Parking',
        'is_chartered' => 'Is Chartered',
        'is_penalty' => 'Is Penalty',
        'is_stamp' => 'Is Stamp (มีตราประทับ)',
        'calculation.parking.discount_type' => 'Discount Type (ประเภทคูปองส่วนลด)',
        'amount_paid' => 'Paid Amount (THB) จำนวนเงินที่ชำระ (บาท)',
        'change' => 'Change Amount (THB) จำนวนเงินทอน (บาท)',
        'amount_to_pay' => 'Total Parking Fee (THB) ค่าจอดรถรวม (บาท)',
        'voucher_image' => 'Voucher Image',
        'created_at' => 'Created At Date (สร้างเมื่อวันที่)',
        'created_at_time' => 'Created At Time (สร้างเมื่อเวลา)',
        'updated_at' => 'Updated at (Date)',
        'updated_at_time' => 'Updated at (Time)',
    ];

    protected array $drawings = [];

    public function __construct(Collection $records, array $columns)
    {
        $this->records = $records;
        $this->columns = $columns;
        $this->tempImageDir = 'temp-images/'.Str::uuid();
    }

    public function array(): array
    {
        return $this->transformRecords()->toArray(); // Already in flattened array form
    }

    public function headings(): array
    {
        return array_map(fn ($col) => $this->columnLabels[$col] ?? $col, $this->columns);
    }

    public function styles(Worksheet $sheet)
    {
        // Style header
        $sheet->getStyle("A1:{$sheet->getHighestColumn()}1")
            ->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle("A1:{$sheet->getHighestColumn()}1")
            ->getFill()->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('003060');
        $sheet->getStyle("A1:{$sheet->getHighestColumn()}1")
            ->getAlignment()->setHorizontal('center')->setVertical('center');

        // Row heights for image rows
        $rowIndex = 2;
        foreach ($this->records as $record) {
            $media = Media::where('model_type', 'App\\Models\\VisitorParking')
                ->where('model_id', $record->id)
                ->orderByDesc('id')
                ->first();

            if ($media) {
                $sheet->getRowDimension($rowIndex)->setRowHeight(85); // Match image height
            }

            $rowIndex++;
        }
    }

    public function bindValue(Cell $cell, $value)
    {
        if (is_numeric($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }
        if (is_string($value) && str_starts_with($value, '=')) {
            $cell->setValueExplicit("'{$value}", DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    protected function transformRecords(): Collection
    {
        $rowIndex = 2;

        return $this->records->map(function ($record) use (&$rowIndex) {
            $unit_to_visit = '-';

            if (! empty($record->visitorLog->visitingArrangements)) {
                $unit_numbers = collect($record->visitorLog->visitingArrangements)
                    ->map(fn ($arrangement) => data_get($arrangement, 'unit.unit_number'))
                    ->filter() // remove null or empty
                    ->unique()
                    ->map(fn ($number) => ucfirst($number)) // capitalize first letter
                    ->implode(', ');

                if ($unit_numbers !== '') {
                    $unit_to_visit = $unit_numbers;
                }
            }

            $freeParkingInMinutes = data_get($record->calculation_records, 'free_parking_minutes');

            // 1st Step
            $arrivalTime = new Carbon($record->visitorLog->arrival_time);
            $departTime = is_null($record->visitorLog->leave_time) ? Carbon::now() : new Carbon($record->visitorLog->leave_time);
            $freeParkingInMinutes = ((new Carbon($freeParkingInMinutes))->hour) * 60 + (new Carbon($freeParkingInMinutes))->minute;

            $timeDifference = $departTime->diff($arrivalTime);
            $hours = ($timeDifference->d * 24) + $timeDifference->h;
            $form = $hours * 60 + $timeDifference->i;
            $hours = floor($form / 60);
            $minutes = $form % 60;
            $seconds = $timeDifference->s;

            // Handle if negative value
            if ($hours < 0 || $minutes < 0 || $seconds < 0) {
                $parkingHours = 0 .__(' Hours');
            } else {
                $parkingHours = "$hours ".__('Hours')." $minutes ".__('Minutes')." $seconds ".__('Seconds');
            }

            // Is Free Parking
            $type = $record->calculation->parking->type ?? Calculation::withTrashed()->find($record->calculation_id)?->parking->type;

            // Is Chartered
            $visitorArrived = new Carbon($record->visitorLog->arrival_time);

            if (is_null($record->visitorLog->leave_time) == true) {
                $visitorDepart = Carbon::now();
            } else {
                $visitorDepart = new Carbon($record->visitorLog->leave_time);
            }

            $visitorDuration = $visitorDepart->diffInMinutes($visitorArrived, true);

            $charteredDuration = new Carbon($record->calculation_records['chartered_duration']);
            $minutes = $charteredDuration->hour;
            $charteredDurationInMinutes = ($minutes * 60) + $charteredDuration->minute;

            // Disount Type
            if (! isset($record->calculation_records['parking'])) {
                $discount_type = 'No parking data';
            }

            $discountType = $record->calculation_records['parking']['discount_type'];

            $discount_type = match ($discountType) {
                DiscountType::NO_DISCOUNT_COUPON->value => str_replace('_', ' ', Str::title(DiscountType::NO_DISCOUNT_COUPON->name)),
                DiscountType::PRICE->value => __('visitor.'.strtolower(DiscountType::PRICE->name)),
                DiscountType::TIME->value => __('visitor.'.strtolower(DiscountType::TIME->name)),
                default => 'Unknown discount type',
            };

            // Change Amount
            $amountToPay = data_get($record, 'amount_to_pay');
            $amountPaid = data_get($record, 'amount_paid');

            $changeAmount = $amountPaid - $amountToPay;

            // At the end, return an array with keys matching your column names
            $data = [
                'visitorLog.visitor_generated_no' => $record?->visitorLog?->visitor_generated_no,
                'visitorLog.visitor.name' => $record?->visitorLog?->visitor?->name,
                'visitorLog.vehicle_type' => VehicleType::tryFrom($record?->visitorLog?->vehicle_type)?->getLabel() ?? '-',
                'visitorLog.vehicle_plate_no' => $record?->visitorLog?->vehicle_plate_no,
                'unit_to_visit' => $unit_to_visit,
                'arrival_date' => date('d-M-Y', strtotime($record->visitorLog->arrival_time)),
                'visitorLog.arrival_time' => date('H:i:s', strtotime($record->visitorLog->arrival_time)),
                'departure_date' => $record?->visitorLog?->leave_time ? date('d-M-Y', strtotime($record?->visitorLog?->leave_time)) : '-',
                'visitorLog.leave_time' => $record?->visitorLog?->leave_time ? date('H:i:s', strtotime($record?->visitorLog?->leave_time)) : '-',
                'parking_hour' => $parkingHours,
                'is_free_parking' => $type === 1 ? 'yes' : 'no',
                'is_chartered' => $visitorDuration >= $charteredDurationInMinutes ? 'yes' : 'no',
                'is_penalty' => $record->is_penalty === 1 ? 'yes' : 'no',
                'is_stamp' => $record->is_stamp === 1 ? 'yes' : 'no',
                'calculation.parking.discount_type' => $discount_type,
                'amount_paid' => strval($record->amount_paid),
                'change' => strval($changeAmount),
                'amount_to_pay' => data_get($record, 'amount_to_pay') < 0 ? '0' : (string) data_get($record, 'amount_to_pay'),
                'voucher_image' => '',
                'created_at' => $record->created_at->format('d-M-Y'),
                'created_at_time' => $record->created_at->format('H:i:s'),
                'updated_at' => $record->updated_at->format('d-M-Y'),
                'updated_at_time' => $record->updated_at->format('H:i:s'),
            ];

            $rowIndex++;

            return array_replace(array_fill_keys($this->columns, null), $data);
        });
    }

    public function drawings()
    {
        $drawings = [];
        $rowIndex = 2; // data starts at row 2

        foreach ($this->records as $i => $record) {
            $media = Media::where('model_type', 'App\\Models\\VisitorParking')
                ->where('model_id', $record->id)
                ->orderByDesc('id')
                ->first();

            if ($media) {
                $cosPath = "parking-fees/{$record->id}/{$media->file_name}";
                if (Storage::disk('cos')->exists(config('app.path.cos').'/'.$cosPath)) {
                    // ensure temp directory
                    Storage::disk('local')->makeDirectory('temp-images');
                    $tempFileName = Str::uuid().'-'.$media->file_name;
                    $tempPath = "{$this->tempImageDir}/{$tempFileName}";
                    Storage::disk('local')->put($tempPath, Storage::disk('cos')->get(config('app.path.cos').'/'.$cosPath));
                    $localPath = storage_path("app/{$tempPath}");

                    if (file_exists($localPath)) {
                        $drawing = new Drawing;
                        $drawing->setName('Voucher Image');
                        $drawing->setPath($localPath);
                        $drawing->setHeight(80);

                        $drawing->setOffsetX(5);
                        $drawing->setOffsetY(5);

                        $colIndex = array_search('voucher_image', $this->columns);
                        $colLetter = $this->excelColLetter($colIndex);
                        $drawing->setCoordinates("{$colLetter}".($i + 2)); // i starts from 0
                        $drawings[] = $drawing;
                    }
                }
            }
            $rowIndex++;
        }

        return $drawings;
    }

    protected function excelColLetter(int $index): string
    {
        $letters = '';
        while ($index >= 0) {
            $letters = chr($index % 26 + 65).$letters;
            $index = intdiv($index, 26) - 1;
        }

        return $letters;
    }
}
