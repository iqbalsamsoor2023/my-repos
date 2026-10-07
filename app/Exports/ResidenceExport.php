<?php

namespace App\Exports;

use Carbon\Carbon;
use App\Enums\Residence\ActivationStatusType;
use App\Enums\Residence\MoobanType;
use App\Enums\Residence\PropertyManagementType;
use App\Enums\Residence\SubType;
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

class ResidenceExport extends DefaultValueBinder implements FromCollection, ShouldAutoSize, WithCustomValueBinder, WithHeadings, WithStyles
{
    protected Collection $records;

    protected array $columns;

    public function __construct(Collection $records, array $columns)
    {
        $this->records = $records;
        $this->columns = $columns;
    }

    protected array $columnLabels = [
        'residence_activation_status_id' => 'Status (สถานะ)',
        'subdistrict.district.province.name_in_english' => 'Province (จังหวัด)',
        'subdistrict.district.name_in_english' => 'District (เขต)',
        'subdistrict.name_in_english' => 'SubDistrict (แขวง)',
        'main_road' => 'Main Road',
        'mooban_type' => 'MooBan Type (ประเภทบ้าน)',
        'sub_type' => 'House Type',
        'name' => 'MooBan Name  (โครงการ)',
        'propertyManagementUser.name' => 'MooBan ID',
        'juritic_name' => 'Juristic Name',
        'juristic_phone' => 'Juristic Phone',
        'units_count' => 'Total Units (บ้านเลขที่ทั้งหมด)',
        'distinct_user_count' => 'Total Residents',
        'sign_up_percentage' => 'Sign Up %',
        'residence_age' => 'Age (อายุ)',
        'subscription_end_date' => 'Subscription End Date (วันที่สิ้นสุด)',
        'developer.name' => 'Developer Name',
        'property_management_type' => 'PM Type',
        'propertyManagement.name' => 'PM Company Name',
        'sgocCompany.name' => 'SG Company Name',
        'subscriptionExpires.expiry_date' => 'SG Expiry Date',
        'created_at' => 'Created At (วันที่สร้าง)',
        'updated_at' => 'Update At',
    ];

    public function collection()
    {
        if ($this->records instanceof Collection) {
            return $this->records->map(function ($record) {
                $mooban_type = collect(MoobanType::cases())
                    ->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()])
                    ->get($record->mooban_type, '-');

                $house_type = SubType::tryFrom((int) $record->sub_type)?->getLabel() ?? '-';

                $signup_percentage = '0%';

                if (in_array($record->residence_activation_status_id, [
                    ActivationStatusType::INACTIVE_DEMO->value,
                    ActivationStatusType::ACTIVE_GT_ONLY->value,
                    ActivationStatusType::ACTIVE_DEMO->value,
                ])) {
                    $signup_percentage = '0%';
                } else {
                    $totalUnits = $record->units_count ?? 0;
                    $distinctUsers = $record->distinct_user_count ?? 0;

                    if ($totalUnits === 0) {
                        $signup_percentage = '0%';
                    } else {
                        $percentage = round(($distinctUsers / $totalUnits) * 100, 2);
                        $signup_percentage = $percentage.'%';
                    }
                }

                $propertyType = PropertyManagementType::options();

                $property_type = $propertyType[$record->property_management_type] ?? '-';

                // Build the full dataset with labels as keys
                $data = [
                    'residence_activation_status_id' => $record?->activationStatus?->status,
                    'subdistrict.district.province.name_in_english' => $record?->subdistrict?->district?->province?->name_in_english.' ('.$record?->subdistrict?->district?->province?->name_in_thai.')',
                    'subdistrict.district.name_in_english' => $record?->subdistrict?->district?->name_in_english.' ('.$record?->subdistrict?->district?->name_in_thai.')',
                    'subdistrict.name_in_english' => $record?->subdistrict?->name_in_english.' ('.$record?->subdistrict?->name_in_thai.')',
                    'main_road' => $record->main_road,
                    'mooban_type' => $mooban_type,
                    'sub_type' => $house_type,
                    'name' => $record->name.' ('.$record->name_th.')',
                    'propertyManagementUser.name' => $record?->propertyManagementUser?->name,
                    'juritic_name' => $record->juristic_details['name'] ?? '-',
                    'juristic_phone' => $record->juristic_details['phone_no'] ?? '-',
                    'units_count' => $record->units()->count(),
                    'distinct_user_count' => $record->unitUsers()->count(),
                    'sign_up_percentage' => $signup_percentage,
                    'residence_age' => $record->residence_age,
                    'subscription_end_date' => Carbon::parse($record->subscription_end_date)->format('d-M-Y'),
                    'developer.name' => $record?->developer?->name,
                    'property_management_type' => $property_type,
                    'propertyManagement.name' => $record?->propertyManagement?->name,
                    'sgocCompany.name' => $record?->sgocCompany?->name,
                    'subscriptionExpires.expiry_date' => $record->subscriptionExpires()->where('type', 'Sgoc')->exists()
                        ? Carbon::parse($record->subscriptionExpires()->where('type', 'Sgoc')->first()->expiry_date)->format('d-M-Y')
                        : 'N/A',
                    'created_at' => $record->created_at->format('d-M-Y H:i:s'),
                    'updated_at' => $record->updated_at->format('d-M-Y H:i:s'),
                ];

                return collect($data)->only($this->columns);
            });
        }

        return collect();
    }

    public function headings(): array
    {
        return collect($this->columns)
            ->filter(fn ($col) => isset($this->columnLabels[$col]))
            ->map(fn ($col) => $this->columnLabels[$col])
            ->values()
            ->toArray();
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
