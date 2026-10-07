<?php

namespace App\Exports\MaintenanceReports;

use PhpOffice\PhpSpreadsheet\Style\Border;
use App\Models\Maintenance;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class PublicMaintenanceReportDetail implements FromView, Responsable, ShouldAutoSize, WithEvents
{
    use Exportable;

    private $fileName = 'Public-Maintenance-Details-Report.xlsx';

    protected $maintenance;

    protected $id;

    public function __construct($id)
    {
        $this->id = $id;
        $this->maintenance = new Maintenance;
    }

    protected function getData()
    {
        return $this->maintenance->with(['maintenanceProgressions', 'maintainable', 'reportedBy', 'media'])->whereId($this->id)->first();
    }

    public function view(): View
    {
        $data = $this->getData();

        return view('maintenances.export.public-report-detail', [
            'data' => $data,
        ]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $data = $this->getData();

                $medias = $data->media;

                if (! empty($medias)) {
                    foreach ($medias as $media) {
                        $imagePath = "/maintenance/$data->id/gallery/$media->file_name";

                        if (Storage::disk('cos')->exists(config('app.path.cos').$imagePath)) {
                            $image = $imagePath;
                        } else {
                            $image = '/public/no-image.jpg';
                        }

                        $date = Carbon::now()->format('Y-m-d');
                        $fileInfo = pathinfo($image);
                        $downloadPath = "temp/$date/".$fileInfo['basename'];

                        if (Storage::disk('cos')->exists(config('app.path.cos').$imagePath)) {
                            $imageFile = Storage::disk('cos')->get(config('app.path.cos').$image);
                        } else {
                            $imageFile = storage_path($image);
                        }
                        Storage::put($downloadPath, $imageFile);

                        // Create drawing object
                        unset($drawing);
                        $drawing = new Drawing;
                        $drawing->setPath(storage_path("app/$downloadPath"));
                        $drawing->setHeight(150);
                        $drawing->setOffsetX(5);
                        $drawing->setOffsetY(5);
                        $drawing->setCoordinates('C21');
                        $drawing->setWorksheet($event->sheet->getDelegate());

                        $drawings[] = $drawing;
                        break;
                    }
                }

                $styleBorder = [
                    'borders' => [
                        'outline' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => '585858'],
                        ],
                    ],
                ];

                $cellRange = ['C4:D4', 'C5:D5', 'C7:D7', 'C8:D8', 'C9:D9', 'B11:B12', 'C11:D12', 'B13:B19', 'C13:D19', 'B20:B21', 'C20:D21', 'B21:B29', 'C21:D29', 'B32', 'B33:B34', 'C32:D33', 'B34', 'C34:D34', 'C36', 'C37', 'C38', 'C39', 'D36', 'D37', 'D38', 'D39', 'B41:B45', 'C41:C45', 'D41:D45'];

                foreach ($cellRange as $value) {
                    $event->sheet->getDelegate()->getStyle($value)->applyFromArray($styleBorder);
                }

                $event->sheet->getDelegate()->getStyle('C12:D18')
                    ->getAlignment()->setWrapText(true);
                $event->sheet->getDelegate()->getStyle('C19:D20')
                    ->getAlignment()->setWrapText(true);
            },
        ];
    }
}
