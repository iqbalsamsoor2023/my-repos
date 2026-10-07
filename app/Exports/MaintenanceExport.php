<?php

namespace App\Exports;

use App\Models\ResidenceAmenityOption;
use App\Models\ResidenceAmenity;
use App\Models\Unit;
use App\Enums\Maintenance\MaintenanceStatus;
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

class MaintenanceExport extends DefaultValueBinder implements FromCollection, ShouldAutoSize, WithCustomValueBinder, WithHeadings, WithStyles
{
    protected Collection $records;

    protected array $columns;

    public function __construct(Collection $records, array $columns)
    {
        $this->records = $records;
        $this->columns = $columns;
    }

    protected array $columnLabels = [
        'maintainable_claim_number' => 'Work Order Number',
        'maintainable_type' => 'MooBan (โครงการ)',
        'maintainable_id' => 'Facility/Amenity (สิ่งอำนวยความสะดวก)', // Public Maintenance
        'issue_description' => 'Issue Description (รายละเอียดปัญหา)',
        'status' => 'Status (สถานะ)',
        'is_verified' => 'Is Verified (อนุมัติ)',
        'appointment_datetime' => 'Appointment DateTime (วันและเวลานัดหมาย)',
        'updated_at' => 'Last Update',
        'rating' => 'Rating (คะแนน)',
        'created_at' => 'Created At Date (สร้างเมื่อวันที่)',
        'created_at_time' => 'Created At Time (สร้างเมื่อเวลา)',
        'reportedBy.name' => 'Reported By (รายงานโดย)',
        'reportedBy.phone_no' => 'Reported Phone Number (เบอร์โทร)',
        'maintainable.unit_number' => 'Unit Number (บ้านเลขที่)', // Private Maintenance
        'amenity' => 'Amenity (สิ่งของอำนวยความสะดวก)', // Private Maintenance
        'warranty_status' => 'Warranty Status (สถานะการรับประกัน)', // Private Maintenance
    ];

    public function collection()
    {
        // Check if $records is indeed a Collection and has items
        if ($this->records instanceof Collection) {
            return $this->records->map(function ($record) {
                $rating = $record->rating ?? 0;

                $stars = ''.str_repeat('★', $rating).str_repeat('☆', 5 - $rating);

                $residence = match ($record->maintainable_type) {
                    ResidenceAmenityOption::class => $record->maintainable?->residenceAmenity?->residence,
                    ResidenceAmenity::class => $record->maintainable?->residence,
                    Unit::class => $record->maintainable?->residence,
                    default => null,
                };

                $residenceName = $residence->name ?? 'N/A';
                $residenceNameThai = $residence->name_th ?? 'N/A';

                $moobanName = match (app()->getLocale()) {
                    'th' => $residenceNameThai ?: $residenceName,
                    'en' => $residenceName ?: $residenceNameThai,
                    default => $residenceName, // fallback
                };

                if (in_array($record->maintainable_type, [
                    ResidenceAmenity::class,
                    ResidenceAmenityOption::class,
                ])) {
                    $data = [
                        'maintainable_claim_number' => $record->maintainable_claim_number,
                        'maintainable_type' => $moobanName,
                        'maintainable_id' => $record->maintainable instanceof ResidenceAmenity
                            ? (app()->getLocale() === 'th'
                                ? optional($record->maintainable->facilityAndAmenity)->name_in_thai ?? '-'
                                : optional($record->maintainable->facilityAndAmenity)->name ?? '-')
                            : ($record->maintainable instanceof ResidenceAmenityOption
                                ? (optional($record->maintainable->residenceAmenity?->facilityAndAmenity)
                                    ? (app()->getLocale() === 'th'
                                        ? optional($record->maintainable->residenceAmenity->facilityAndAmenity)->name_in_thai.' ('.$record->maintainable->name_in_thai.')'
                                        : optional($record->maintainable->residenceAmenity->facilityAndAmenity)->name.' ('.$record->maintainable->name.')'
                                    )
                                    : '-')
                                : '-'
                            ),
                        'issue_description' => $record->issue_description,
                        'status' => MaintenanceStatus::tryFrom($record->status)?->label() ?? 'Unknown',
                        'is_verified' => $record->is_verified,
                        'appointment_datetime' => $record->appointment_datetime,
                        'updated_at' => $record->updated_at->format('Y-m-d H:i'),
                        'rating' => $stars,
                        'created_at' => $record->created_at->format('Y-m-d'),
                        'created_at_time' => $record->created_at->format('H:i:s'),
                        'reportedBy.name' => $record->reportedBy ? $record->reportedBy->name : 'N/A',
                        'reportedBy.phone_no' => $record->reportedBy ? $record->reportedBy->phone_no : 'N/A',
                    ];

                    return collect($data)->only($this->columns);
                } elseif ($record->maintainable_type === Unit::class) {
                    $current_date = Carbon::now()->format('Y-m-d');
                    $move_in_date = $record->maintainable->move_in_at;
                    $warranty_status = null;

                    if (isset($record->amenity)) {
                        if ($record->amenity['period_type'] == 'year') {
                            $warranty_period = Carbon::parse($move_in_date)->addYear($record->amenity['warranty_period'])->format('Y-m-d');
                        } else {
                            $warranty_period = Carbon::parse($move_in_date)->addMonths($record->amenity['warranty_period'])->format('Y-m-d');
                        }

                        $warranty_status = ($current_date > $warranty_period) ? 'expired' : 'in warranty';
                    }

                    $data = [
                        'maintainable_claim_number' => $record->maintainable_claim_number,
                        'maintainable_type' => $moobanName,
                        'maintainable.unit_number' => $record?->maintainable?->unit_number,
                        'amenity' => is_null($record->amenity) == false ? $record->amenity['amenity_name'] : 'Others',
                        'issue_description' => $record->issue_description,
                        'warranty_status' => $warranty_status,
                        'status' => MaintenanceStatus::tryFrom($record->status)?->label() ?? 'Unknown',
                        'is_verified' => $record->is_verified,
                        'appointment_datetime' => $record->appointment_datetime,
                        'updated_at' => $record->updated_at->format('Y-m-d H:i'),
                        'rating' => $stars,
                        'created_at' => $record->created_at->format('Y-m-d'),
                        'created_at_time' => $record->created_at->format('H:i:s'),
                        'reportedBy.name' => $record->reportedBy ? $record->reportedBy->name : 'N/A',
                        'reportedBy.phone_no' => $record->reportedBy ? $record->reportedBy->phone_no : 'N/A',
                    ];

                    return collect($data)->only($this->columns);
                }

                return collect();
            });
        }

        return collect(); // Fallback if records is not a collection
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
