<?php

namespace App\Filament\Resources\PublicMaintenances\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\PublicMaintenances\Widgets\PublicMaintenancesChart;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Forms\Components\DatePicker;
use App\Enums\Maintenance\MaintenanceStatus;
use App\Filament\Resources\PublicMaintenances\PublicMaintenanceResource;
use App\Models\Maintenance;
use App\Models\ResidenceAmenity;
use App\Models\ResidenceAmenityOption;
use App\Models\Unit;
use Filament\Forms;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\App;

class ListPublicMaintenances extends ListRecords
{
    protected static string $resource = PublicMaintenanceResource::class;

    public function getTitle(): string
    {
        return __('menu.public_maintenances');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('menu.new_public_maintenance')),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            PublicMaintenancesChart::class,
        ];
    }

    public function getTableFilters(): array
    {
        $locale = App::getLocale();

        return [
            Filter::make('work_order_no')
                ->schema([
                    TextInput::make('maintainable_claim_number')
                        ->label(__('Work Order Number')),
                ])
                ->query(function (Builder $query, array $data) {
                    if (isset($data['maintainable_claim_number'])) {
                        return $query->where('maintainable_claim_number', $data['maintainable_claim_number']);
                    }
                }),
            Filter::make('amenity_filters')
                ->columnSpan(2)
                ->schema([
                    Fieldset::make('Amenity/Facility')
                        ->columns(2)
                        ->schema([
                            Select::make('residence')
                                ->label(__('app.mooban_or_residence'))
                                ->options(list_residences())
                                ->searchable()
                                ->preload()
                                ->live(),
                            Select::make('maintainable_id')
                                ->label(__('Amenity/Facility'))
                                ->options(function (Get $get) use ($locale) {
                                    $residenceId = (array) $get('residence'); // Ensure it's an array

                                    if (empty($residenceId)) {
                                        return [];
                                    }

                                    return ResidenceAmenity::with(['facilityAndAmenity', 'residenceAmenityOptions'])
                                        ->where('residence_id', $residenceId)
                                        ->where('is_active', true)
                                        ->where('is_claimable', true)
                                        ->get()
                                        ->flatMap(function ($amenity) use ($locale) {
                                            $facility = $amenity->facilityAndAmenity;

                                            $facilityName = $facility
                                                ? ($locale === 'th' ? $facility->name_in_thai : $facility->name)
                                                : '-';

                                            if ($amenity->residenceAmenityOptions->isNotEmpty()) {
                                                return $amenity->residenceAmenityOptions->mapWithKeys(function ($option) use ($facilityName, $locale) {
                                                    $optionName = $locale === 'th' ? $option->name_in_thai : $option->name;

                                                    return ["option-{$option->id}" => "{$facilityName} ({$optionName})"];
                                                });
                                            }

                                            return ["amenity-{$amenity->id}" => $facilityName];
                                        })
                                        ->toArray();
                                })
                                ->searchable()
                                ->preload()
                                ->live(),
                        ]),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    if (! empty($data['residence'])) {
                        $residenceId = $data['residence'];

                        $query->where(function ($query) use ($residenceId) {
                            $query
                                ->whereHasMorph(
                                    'maintainable',
                                    [ResidenceAmenity::class],
                                    fn ($q) => $q->where('residence_id', $residenceId)
                                )
                                ->orWhereHasMorph(
                                    'maintainable',
                                    [ResidenceAmenityOption::class],
                                    fn ($q) => $q->whereHas('residenceAmenity', fn ($sub) => $sub->where('residence_id', $residenceId))
                                );
                        });
                    }

                    if (! empty($data['maintainable_id'])) {
                        [$type, $id] = explode('-', $data['maintainable_id'], 2);

                        if ($type === 'amenity') {
                            $query->whereHasMorph('maintainable', ResidenceAmenity::class, function ($q) use ($id) {
                                $q->where('id', $id);
                            });
                        }

                        if ($type === 'option') {
                            $query->whereHasMorph('maintainable', ResidenceAmenityOption::class, function ($q) use ($id) {
                                $q->where('id', $id);
                            });
                        }
                    }

                    return $query;
                }),
            SelectFilter::make('status')
                ->label(__('app.status'))
                ->options(collect(MaintenanceStatus::cases())
                    ->mapWithKeys(fn ($status) => [$status->value => $status->label()])
                    ->toArray()
                ),
            TernaryFilter::make('is_verified')
                ->label(__('app.is_verified')),
            Filter::make('created_at')
                ->schema([
                    DatePicker::make('report_from')
                        ->label(__('Report From')),
                    DatePicker::make('report_until')
                        ->label(__('Report Until')),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            $data['report_from'],
                            fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                        )
                        ->when(
                            $data['report_until'],
                            fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                        );
                }),
        ];
    }
}
