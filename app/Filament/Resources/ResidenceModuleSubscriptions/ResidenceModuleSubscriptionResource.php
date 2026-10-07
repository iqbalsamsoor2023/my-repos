<?php

namespace App\Filament\Resources\ResidenceModuleSubscriptions;

use App\Actions\Audit\CreateAuditAction;
use App\Enums\Residence\Features;
use App\Exports\ResidenceExport;
use App\Filament\Resources\ResidenceModuleSubscriptions\Pages\ListResidenceModuleSubscriptions;
use App\Models\Residence;
use App\Models\ResidenceActivationStatus;
use App\Services\FilamentExport\FilamentExportBulkAction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class ResidenceModuleSubscriptionResource extends Resource
{
    protected static ?string $model = Residence::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDeviceTablet;

    protected static ?int $navigationSort = 6;

    public static function getNavigationGroup(): string
    {
        return __('menu.big_data_management');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->hasAnyRole(['Super Admin', 'Admin']);
    }

    public static function canAccess(): bool
    {
        if (auth()->user()->hasAnyRole(['Super Admin', 'Admin'])) {
            return true;
        }

        return false;
    }

    public static function getModelLabel(): string
    {
        return __('menu.residence_module_subscription');
    }

    public static function getPluralModelLabel(): string
    {
        return __('menu.residence_module_subscriptions');
    }

    public static function table(Table $table): Table
    {
        return static::getTable($table);
    }

    public static function getTable(Table $table): Table
    {
        $residenceFeatures = new ListResidenceModuleSubscriptions;

        return $table
            ->columns([
                TextColumn::make('residence_activation_status_id')
                    ->label(__('app.status'))
                    ->badge()
                    ->formatStateUsing(function (string $state): string {
                        $statusMapping = ResidenceActivationStatus::pluck('status', 'id');

                        return $statusMapping[$state];
                    })
                    ->color(function ($state) {
                        return match ($state) {
                            1 => 'gray',
                            2 => 'red',
                            3 => 'pink',
                            4 => 'blue',
                            5 => 'green',
                            6 => 'orange',
                            default => 'black', // Fallback color
                        };
                    })
                    ->toggleable(),
                TextColumn::make('subdistrict.district.province.name_in_english')
                    ->description(fn (Residence $record): string => $record->subdistrict->district->province->name_in_thai ?? '-')
                    ->label(__('app.province'))
                    ->copyable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('subdistrict.district.name_in_english')
                    ->description(fn (Residence $record): string => $record->subdistrict->district->name_in_thai ?? '-')
                    ->label(__('app.district'))
                    ->copyable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('subdistrict.name_in_english')
                    ->description(fn (Residence $record): string => $record->subdistrict->name_in_thai ?? '-')
                    ->label(__('app.subdistrict'))
                    ->copyable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('name')
                    ->label(__('residence.mooban_name'))
                    ->description(fn (Residence $record): string => $record->name_th ?? '-')
                    ->copyable()
                    ->searchable(query: function ($query, $search) {
                        $query->where('name', 'like', "%{$search}%")
                            ->orWhere('name_th', 'like', "%{$search}%");
                    })
                    ->toggleable(),
                TextColumn::make('propertyManagementUser.name')
                    ->label(__('residence.mooban_id'))
                    ->searchable()
                    ->copyable()
                    ->toggleable(),

                // Feature columns (RMS2.1 - RMS2.9)
                static::rmsFeatureColumn('RMS2.1 Sales (sm)', Features::SALES_MANAGEMENT),
                static::rmsFeatureColumn('RMS2.2 Re-Sales (rtm)', Features::RESALE_AND_TENANCY),
                static::rmsFeatureColumn('RMS2.3 Property Mgmt (pm)', Features::PROPERTY_MANAGEMENT),
                static::rmsFeatureColumn('RMS2.4 Security (sc)', Features::SECURITY_MANAGEMENT),
                static::rmsFeatureColumn('RMS2.5 Facilities (tc)', Features::FACILITIES_MANAGEMENT),
                static::rmsFeatureColumn('RMS2.6 Accounting (ac)', Features::ACCOUNTING_MANAGEMENT),
                static::rmsFeatureColumn('RMS2.7 Reception (rc)', Features::RECEPTION_MANAGEMENT),
                static::rmsFeatureColumn('RMS2.8 Cleanliness (md)', Features::CLEANLINESS_MANAGEMENT),
                static::rmsFeatureColumn('RMS2.9 Communication (cm)', Features::CONTACT),
            ])
            ->defaultSort('updated_at', 'desc')
            ->recordActions([
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
            ])
            ->filters($residenceFeatures->getTableFilters(), layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(3)
            ->toolbarActions([
                FilamentExportBulkAction::make('export')
                    ->fileName('Residence-Report')
                    ->disableAdditionalColumns()
                    ->disableCsv()
                    ->disablePdf()
                    ->disableFilterColumns()
                    ->action(function (Component $livewire) {
                        $columns = collect($livewire->getTable()->getColumns())
                            ->filter(fn ($col) => $col->isVisible())
                            ->map(fn ($col) => $col->getName())
                            ->values()
                            ->toArray();

                        $fileNameInput = $livewire->mountedActions[0]['data']['file_name'] ?? 'export';
                        $fileName = Str::slug($fileNameInput).'.xlsx';

                        $ids = $livewire->getSelectedTableRecords()->pluck('id')->toArray();
                        $residences = Residence::whereIn('id', $ids)->latest('id')->get();

                        $audit = new CreateAuditAction;
                        $audit->execute(new Request([
                            'user_type' => get_class(auth()->user()),
                            'user_id' => auth()->id(),
                            'event' => 'exported',
                            'old_values' => [],
                            'new_values' => ['action' => 'exported residence records'],
                            'url' => request()->fullUrl(),
                            'ip_address' => request()->ip(),
                            'user_agent' => request()->userAgent(),
                        ]));

                        return Excel::download(new ResidenceExport($residences, $columns), $fileName);
                    }),
            ])
            ->paginated([10, 25, 50]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListResidenceModuleSubscriptions::route('/'),
        ];
    }

    public static function rmsFeatureColumn(string $label, Features $featureEnum): TextColumn
    {
        return TextColumn::make(Str::snake($featureEnum->name))
            ->label($label)
            ->badge()
            ->color(fn ($state) => $state ? 'success' : 'danger')
            ->getStateUsing(fn ($record) => $record->residenceFeatures
                ->firstWhere('feature_id', $featureEnum->value)?->is_active === 1
            )
            ->formatStateUsing(fn (bool $state) => $state ? 'Active' : 'Inactive');
    }
}
