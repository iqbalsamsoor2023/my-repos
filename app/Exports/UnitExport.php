<?php

namespace App\Exports;

use App\Enums\ResalesManagement\ResalesManagementStatus;
use App\Enums\SalesManagement\ConstructionProgressEnum;
use App\Enums\TenancyManagement\TenancyManagementStatus;
use App\Enums\Unit\StatusType;
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

class UnitExport extends DefaultValueBinder implements
    FromCollection,
    ShouldAutoSize,
    WithCustomValueBinder,
    WithHeadings,
    WithStyles
{
    protected Collection $records;

    protected array $columns;

    public function __construct(Collection $records, array $columns)
    {
        $this->records = $records;
        $this->columns = $columns;
    }

    protected array $columnLabels = [
        'residence.name' => 'MooBan (โครงการ)',
        'unit_number' => 'Unit Number (บ้านเลขที่)',
        'home_id' => 'Home ID (รหัสบ้าน)',
        'move_in_at' => 'Move In At (วันที่เข้าอยู่)',
        'invitation_code_owner' => 'Invitation Code Owner (รหัสลงทะเบียน เจ้าของ)',
        'invitation_code_tenant' => 'Invitation Code Tenant (รหัสลงทะเบียน ผู้เช่า)',
        'block' => 'Block (บล็อค)',
        'street' => 'Soi (ถนน)',
        'floor' => 'Floor (ชั้น)',
        'space_size' => 'Space Size (ขนาดพื้นที่)',
        'land_size' => 'Land Size (ขนาดที่ดิน)',
        'charge_type' => 'Charge Type',
        'maintenance_cycle' => 'Maintenance Cycle',
        'maintenance_cutoff_day' => 'Maintenance Cut-Off Day',
        'status' => 'Status (สถานะ)',
        'rentAdvertisement.tenancy_status' => 'Tenancy Status (สถานะการเช่า)',
        'resaleAdvertisement.status' => 'Resale Status (สถานะการขายต่อ)',
        'construction_progress' => 'Construction Progress (ความก้าวหน้าในการก่อสร้าง)',
        'created_at' => 'Created At (วันที่สร้าง)',
        'updated_at' => 'Updated At',
    ];

    public function collection()
    {
        return $this->records->map(function ($record) {

            $data = [
                'residence.name' => $record?->residence?->name .
                    ($record?->residence?->name_th ? ' (' . $record->residence->name_th . ')' : ''),
                'unit_number' => $record->unit_number,
                'home_id' => $record->home_id,
                'move_in_at' => $record->move_in_at
                    ? date('d-M-y', strtotime($record->move_in_at))
                    : null,
                'invitation_code_owner' => $record->invitation_code_owner,
                'invitation_code_tenant' => $record->invitation_code_tenant,
                'block' => $record->block,
                'street' => $record->soi,
                'floor' => $record->floor,
                'space_size' => $record->space_size,
                'land_size' => $record->land_size,
                'charge_type' => $record->charge_type,
                'maintenance_cycle' => $record->maintenance_cycle,
                'maintenance_cutoff_day' => $record->maintenance_cutoff_day,
                'status' => StatusType::tryFrom($record->status)?->getLabel() ?? '-',
                'rentAdvertisement.tenancy_status' => TenancyManagementStatus::tryFrom($record?->rentAdvertisement?->status)?->getLabel() ?? '-',
                'resaleAdvertisement.status' => ResalesManagementStatus::tryFrom($record?->resaleAdvertisement?->status)?->getLabel() ?? '-',
                'construction_progress' => ConstructionProgressEnum::tryFrom($record->construction_progress)?->getLabel() ?? '-',
                'created_at' => optional($record->created_at)->toDateTimeString(),
                'updated_at' => optional($record->updated_at)->toDateTimeString(),
            ];

            $row = [];

            foreach ($this->columns as $column) {
                $row[] = $data[$column] ?? null;
            }

            return $row;
        });
    }

    public function headings(): array
    {
        return array_map(function ($column) {
            return $this->columnLabels[$column] ?? $column;
        }, $this->columns);
    }

    public function styles(Worksheet $sheet)
    {
        $headerRange = 'A1:' . $sheet->getHighestColumn() . '1';

        $sheet->getStyle($headerRange)
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setRGB('003060');

        $sheet->getStyle($headerRange)
            ->getFont()
            ->setBold(true)
            ->getColor()
            ->setRGB('FFFFFF');

        $sheet->getStyle($headerRange)
            ->getAlignment()
            ->setHorizontal('center')
            ->setVertical('center');
    }

    public function bindValue(Cell $cell, $value)
    {
        if (is_numeric($value)) {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);
            return true;
        }

        return parent::bindValue($cell, $value);
    }
}