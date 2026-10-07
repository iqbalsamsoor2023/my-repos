<?php

namespace App\Exports;

use Maatwebsite\Excel\Excel;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeWriting;
use Maatwebsite\Excel\Files\LocalTemporaryFile;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class TemplateBillReminder implements WithColumnFormatting, WithEvents
{
    use Exportable;

    public $units;

    public function __construct($units)
    {
        $this->units = $units;
    }

    public function registerEvents(): array
    {
        return [
            BeforeWriting::class => function (BeforeWriting $event) {
                $templateFile = new LocalTemporaryFile(storage_path('templates/imports/MyMooBan Bill Reminder Import Template.xlsx'));
                $event->writer->reopen($templateFile, Excel::XLSX);
                $sheet = $event->writer->getSheetByIndex(0);

                $this->populateSheet($sheet);

                $event->writer->getSheetByIndex(0)->export($event->getConcernable()); // call the export on the first sheet

                return $event->getWriter()->getSheetByIndex(0);
            },
        ];
    }

    private function populateSheet($sheet)
    {
        // https://github.com/SpartnerNL/Laravel-Excel/issues/1963
        // Party starts at row 3
        $iteration = 3;

        foreach ($this->units as $unit) {
            // Create cell definitions
            $A = 'A'.($iteration);
            $B = 'B'.($iteration);
            $C = 'C'.($iteration);
            $D = 'D'.($iteration);
            $E = 'E'.($iteration);
            $F = 'F'.($iteration);
            $G = 'G'.($iteration);
            $H = 'H'.($iteration);

            $homeID = $unit->home_id;
            $unitNumber = $unit->unit_number;

            // Populate dynamic content
            $sheet->setCellValueExplicit($A, $homeID, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($B, $unitNumber, DataType::TYPE_STRING);
            $sheet->setCellValue($C, '');
            $sheet->setCellValue($D, '');
            $sheet->setCellValue($E, '');
            $sheet->setCellValue($F, 'All');
            $sheet->setCellValue($G, '');

            $iteration++;
        }
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT,
            'B' => NumberFormat::FORMAT_TEXT,
            'C' => NumberFormat::FORMAT_TEXT,
            'E' => NumberFormat::FORMAT_NUMBER_00,
        ];
    }
}
