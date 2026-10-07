<?php

namespace App\Filament\Resources\Residences\Pages;

use App\Filament\Resources\Residences\ResidenceResource;
use App\Filament\Resources\Residences\Widgets\MoobanActivationStatusOverview;
use App\Filament\Resources\Residences\Widgets\MoobanActivationStatusStatsOverview;
use App\Filament\Resources\Residences\Widgets\MoobanAgeChart;
use App\Filament\Resources\Residences\Widgets\MoobanCreationChart;
use App\Filament\Resources\Residences\Widgets\MoobanTypesOverview;
use App\Filament\Resources\Residences\Widgets\PublicTypeChart;
use App\Filament\Resources\Residences\Widgets\PublicTypeStatsOverview;
use App\Services\ResidenceImportService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Response;

class ListResidences extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = ResidenceResource::class;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    public function getHeaderWidgetsColumns(): int|array
    {
        return 3;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            PublicTypeChart::class,
            PublicTypeStatsOverview::class,
            MoobanActivationStatusOverview::class,
            MoobanActivationStatusStatsOverview::class,
            MoobanTypesOverview::class,
            MoobanAgeChart::class,
            MoobanCreationChart::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('UploadData')
                ->label(__('app.upload_data'))
                ->action(fn (array $data) => $this->openImportExcel($data))
                ->schema([
                    FileUpload::make('upload')
                        ->label(__('app.upload'))
                        ->preserveFilenames()
                        ->disk('local')
                        ->directory('uploads')
                        ->storeFileNamesIn('uploads'),
                ]),
            Action::make('downloadTemplate')
                ->label(__('app.download_template'))
                ->action('downloadFileTemplate'),
            CreateAction::make()
                ->label(__('menu.new_residence')),
        ];
    }

    /**
     * @param  array{upload: string}  $data
     */
    public function openImportExcel(array $data): void
    {
        $result = app(ResidenceImportService::class)->import($data['upload']);

        $notification = Notification::make()->title($result['title']);

        if (filled($result['body'])) {
            $notification->body(nl2br((string) $result['body']));
        }

        match ($result['status']) {
            'success' => $notification->success(),
            'danger' => $notification->danger()->persistent(),
            default => $notification->warning()->persistent(),
        };

        $notification->send();
    }

    public function downloadFileTemplate()
    {
        $filePath = storage_path('templates/imports/MyMooBan Residence Template.xlsx');

        return Response::download($filePath);
    }
}
