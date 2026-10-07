<?php

namespace App\Filament\Resources\Visitors\Pages;

use App\Filament\Resources\Visitors\Tables\HistoricalVisitorsTable;
use App\Filament\Resources\Visitors\VisitorResource;
use App\Filament\Resources\Visitors\Widgets\VisitorHistoricalReportDownloadWidget;
use App\Models\VisitorLogArchive;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ListHistoricalVisitors extends ListRecords
{
    protected static string $resource = VisitorResource::class;

    public function getTitle(): string
    {
        return __('visitor.historical_visitors');
    }

    public function getDefaultTableRecordsPerPageSelectOption(): int
    {
        return 10;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view live')
                ->icon('heroicon-o-arrow-left')
                ->color('primary')
                ->url(route('filament.admin.resources.visitors.index'))
                ->label(__('visitor.view_live_visitors')),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            VisitorHistoricalReportDownloadWidget::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $query = VisitorLogArchive::query()
            ->with([
                'visitor',
                'residence',
                'visitingArrangements.user',
                'visitingArrangements.unit',
                'visitorParking',
                'courierLogisticPartner',
                'foodDeliveryLogisticPartner',
                'preregisterVisitor',
                'visitorCard',
            ]);

        if ($user->hasAnyRole(['Property Management', 'Property Management Operation Center'])) {
            $residenceIds = $user->hasRole('Property Management')
                ? [get_residence_by_property_management($user->id)->id]
                : get_residence_by_property_management_operation_center($user->id);

            $query->whereIn('residence_id', $residenceIds);
        } elseif ($user->hasRole('Developer')) {
            $residenceIds = get_residence_by_developer($user->id);
            $query->whereIn('residence_id', $residenceIds);
        }

        return $query
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc');
    }

    public function table(Table $table): Table
    {
        return HistoricalVisitorsTable::configure($table)
            ->query($this->getEloquentQuery());
    }
}
