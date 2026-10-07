<?php

namespace App\Filament\Resources\UnitUsers\Pages;

use App\Filament\Resources\UnitUsers\UnitUserResource;
use App\Filament\Resources\UnitUsers\Widgets\ResidentCreationChart;
use App\Filament\Resources\UnitUsers\Widgets\ResidentsAgeChart;
use App\Filament\Resources\UnitUsers\Widgets\ResidentsAgeStatsOverview;
use App\Filament\Resources\UnitUsers\Widgets\ResidentsAllActionCancelledServiceChart;
use App\Filament\Resources\UnitUsers\Widgets\ResidentsByCountryChart;
use App\Filament\Resources\UnitUsers\Widgets\ResidentsGenderChart;
use App\Filament\Resources\UnitUsers\Widgets\ResidentsGenderStatsOverview;
use App\Filament\Resources\UnitUsers\Widgets\ResidentsNationalityStatsOverview;
use App\Filament\Resources\UnitUsers\Widgets\ResidentsOwnerTenantChart;
use App\Filament\Resources\UnitUsers\Widgets\ResidentsOwnerTenantStatsOverview;
use App\Services\ResidenceService;
use App\Services\UnitUserImportService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Response;

class ListUnitUsers extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = UnitUserResource::class;

    public function getHeaderWidgetsColumns(): int|array
    {
        return 3;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ResidentsAllActionCancelledServiceChart::class,
            ResidentsGenderChart::class,
            ResidentsGenderStatsOverview::class,
            ResidentsOwnerTenantChart::class,
            ResidentsOwnerTenantStatsOverview::class,
            ResidentsByCountryChart::class,
            ResidentsNationalityStatsOverview::class,
            ResidentsAgeChart::class,
            ResidentsAgeStatsOverview::class,
            ResidentCreationChart::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('UploadData')
                ->label(__('app.upload_data'))
                ->action(fn (array $data) => $this->openImportExcel($data))
                ->schema([
                    Select::make('residence_id')
                        ->label(__('app.mooban_or_residence'))
                        ->searchable()
                        ->optionsLimit(50)
                        ->getSearchResultsUsing(fn (string $search): array => ResidenceService::searchResidencesForUser($search))
                        ->getOptionLabelUsing(fn ($value): ?string => ResidenceService::getResidenceLabelForUser($value))
                        ->required(),
                    FileUpload::make('upload')
                        ->label(__('app.upload'))
                        ->preserveFilenames()
                        ->disk('local')
                        ->directory('uploads')
                        ->storeFileNamesIn('uploads')
                        ->required(),
                ]),
            Action::make('downloadTemplate')
                ->label(__('app.download_template'))
                ->action('downloadFileTemplate'),
            CreateAction::make()
                ->label(__('menu.new_resident')),
        ];
    }

    /**
     * @param  array{residence_id: int|string, upload: string}  $data
     */
    public function openImportExcel(array $data)
    {
        $result = app(UnitUserImportService::class)->import(
            (int) $data['residence_id'],
            $data['upload']
        );

        $notification = Notification::make()->title($result['title']);

        if (filled($result['body'])) {
            $notification->body(nl2br((string) $result['body']));
        }

        match ($result['status']) {
            'success' => $notification->success(),
            'danger' => $notification->danger()->persistent(),
            default => $notification->warning()->persistent(),
        };

        return $notification->send();
    }

    public function downloadFileTemplate()
    {
        $filePath = storage_path('templates/imports/MyMooBan Resident Template.xlsx');

        return Response::download($filePath);
    }
}
