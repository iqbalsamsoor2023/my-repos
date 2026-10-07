<?php

namespace App\Filament\Resources\Units\Pages;

use App\Filament\Resources\Units\UnitResource;
use App\Filament\Resources\Units\Widgets\HouseTypeByResidenceChart;
use App\Filament\Resources\Units\Widgets\HouseTypeByResidenceStatsOverview;
use App\Filament\Resources\Units\Widgets\HouseTypeStatsOverview;
use App\Filament\Resources\Units\Widgets\LivingStatusChart;
use App\Filament\Resources\Units\Widgets\OwnerTenantByResidenceChart;
use App\Filament\Resources\Units\Widgets\OwnerTenantByResidenceStatsOverview;
use App\Filament\Resources\Units\Widgets\SectionHeadingHouseType;
use App\Filament\Resources\Units\Widgets\SectionHeadingSubType;
use App\Filament\Resources\Units\Widgets\SignUpRateChart;
use App\Filament\Resources\Units\Widgets\SignUpRateStatsOverview;
use App\Filament\Resources\Units\Widgets\SubTypeStatsOverview;
use App\Filament\Resources\Units\Widgets\UnitCreationChart;
use App\Filament\Resources\Units\Widgets\UnitStatusStatsOverview;
use App\Models\Unit;
use App\Services\UnitImportService;
use App\Support\SpreadsheetImportSupport;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;

class ListUnits extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = UnitResource::class;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    public function getHeaderWidgetsColumns(): int|array
    {
        return 3;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            SectionHeadingSubType::class,
            SubTypeStatsOverview::class,
            SectionHeadingHouseType::class,
            HouseTypeStatsOverview::class,
            UnitCreationChart::class,
            LivingStatusChart::class,
            UnitStatusStatsOverview::class,
            HouseTypeByResidenceChart::class,
            HouseTypeByResidenceStatsOverview::class,
            OwnerTenantByResidenceChart::class,
            OwnerTenantByResidenceStatsOverview::class,
            SignUpRateChart::class,
            SignUpRateStatsOverview::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        $canCreateUnits = Gate::allows('create', Unit::class);

        return [
            Action::make('uploadData')
                ->label(__('app.upload_data'))
                ->action(fn (array $data) => $this->openImportExcel($data))
                ->schema([
                    Select::make('residence_id')
                        ->label(__('app.mooban_or_residence'))
                        ->searchable()
                        ->options(list_residences())
                        // ->options(fn(Component $livewire) =>
                        //     $livewire instanceof ListUnits
                        //         ? list_create_residences()
                        //         : list_residences()
                        // )
                        ->required(),
                    FileUpload::make('upload')
                        ->label(__('app.upload'))
                        ->preserveFilenames()
                        ->disk('local')
                        ->directory('uploads')
                        ->storeFileNamesIn('uploads'),
                ])
                ->visible($canCreateUnits),
            Action::make('downloadTemplate')
                ->label(__('app.download_template'))
                ->action('downloadFileTemplate')
                ->visible($canCreateUnits),
            CreateAction::make()
                ->visible($canCreateUnits),

        ];
    }

    /**
     * @param  array{residence_id: int|string, upload: string}  $data
     */
    public function openImportExcel(array $data)
    {
        $result = app(UnitImportService::class)->import(
            (int) $data['residence_id'],
            $data['upload']
        );

        if (! empty($result['errors'])) {
            return Notification::make()
                ->title(__('unit.import_message.import_failed'))
                ->body(nl2br(SpreadsheetImportSupport::formatRowErrors($result['errors'])))
                ->warning()
                ->persistent()
                ->send();
        }

        return redirect('/admin/units');
    }

    public function downloadFileTemplate()
    {
        $filePath = storage_path('templates/imports/MyMooBan Residence Unit Template.xlsx');

        return Response::download($filePath);
    }
}
