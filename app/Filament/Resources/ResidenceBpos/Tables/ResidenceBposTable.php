<?php

namespace App\Filament\Resources\ResidenceBpos\Tables;

use App\Enums\Company\CompanyTypeEnum;
use App\Enums\Residence\MoobanType;
use App\Enums\Residence\PropertyManagementType;
use App\Enums\User\RoleType;
use App\Exports\ResidenceExport;
use App\Helpers\CompanyHelper;
use App\Models\Residence;
use App\Services\FilamentExport\FilamentExportBulkAction;
use App\Services\FilamentExport\FilamentTableExportSupport;
use App\Support\BpoSoftwareSupplierFilterHelper;
use App\Support\ResidenceLocationFilterSupport;
use App\Support\ResidenceOptionsSupport;
use App\Support\WidgetColorPalette;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Fieldset;
use Filament\Tables\Columns\ColumnGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class ResidenceBposTable
{
    public static function configure(Table $table): Table
    {
        $user = Filament::auth()->user();
        $activationStatusOptions = ResidenceOptionsSupport::activationStatusOptions();
        $suppliers = ResidenceOptionsSupport::bpoSupplierOptions();
        $securityGuardCountOptions = ResidenceOptionsSupport::securityGuardCountOptions();

        return $table
            ->columns([
                TextColumn::make('residence_activation_status_id')
                    ->label(__('app.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $activationStatusOptions[$state] ?? '-')
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
                        $query->where('residences.name', 'like', "%{$search}%")
                            ->orWhere('residences.name_th', 'like', "%{$search}%");
                    })
                    ->toggleable(),
                TextColumn::make('propertyManagementUser.name')
                    ->label(__('residence.mooban_id'))
                    ->searchable()
                    ->copyable()
                    ->toggleable(),
                TextColumn::make('developer.name')
                    ->label(__('residence.developer_name'))
                    ->description(fn (Residence $record): string => $record->developer?->name_th ?? '-')
                    ->toggleable(),
                TextColumn::make('property_management_type')
                    ->label(__('residence.property_management_type'))
                    ->badge()
                    ->formatStateUsing(function (string $state): string {
                        $types = WidgetColorPalette::propertyManagementTypes();

                        if (isset($types[(int) $state]['label'])) {
                            return $types[(int) $state]['label'];
                        }

                        // Fallback to enum full label
                        $propertyType = PropertyManagementType::options();

                        return $propertyType[$state] ?? '-';
                    })
                    ->color(fn ($state) => self::getPropertyManagementTypeColor($state))
                    ->toggleable(),
                TextColumn::make('propertyManagement.name')
                    ->label(__('residence.property_management_company_name'))
                    ->description(fn (Residence $record): string => $record->propertyManagement?->name_th ?? '-')
                    ->toggleable(),
                ColumnGroup::make(__('residence.juristic_person_details'), [
                    TextColumn::make('juristic_details.name')
                        ->label(__('residence.juristic_name'))
                        ->getStateUsing(fn (Model $record): string => $record->juristic_details['name'] ?? '-')
                        ->searchable(query: function ($query, $search) {
                            $query->where('residences.juristic_details->name', 'like', "%{$search}%");
                        })
                        ->copyable()
                        ->toggleable(),
                    TextColumn::make('juristic_details.phone_no')
                        ->label(__('residence.juristic_phone_no'))
                        ->getStateUsing(fn (Model $record): string => $record->juristic_details['phone_no'] ?? '-')
                        ->searchable(query: function ($query, $search) {
                            $query->where('residences.juristic_details->phone_no', 'like', "%{$search}%");
                        })
                        ->copyable()
                        ->toggleable(),
                    TextColumn::make('juristic_details.email')
                        ->label(__('residence.juristic_email'))
                        ->getStateUsing(fn (Model $record): string => $record->juristic_details['email'] ?? '-')
                        ->searchable(query: function ($query, $search) {
                            $query->where('residences.juristic_details->email', 'like', "%{$search}%");
                        })
                        ->copyable()
                        ->toggleable(),
                    TextColumn::make('juristic_details.line_id')
                        ->label(__('residence.juristic_line_id'))
                        ->getStateUsing(fn (Model $record): string => $record->juristic_details['line_id'] ?? '-')
                        ->searchable(query: function ($query, $search) {
                            $query->where('residences.juristic_details->line_id', 'like', "%{$search}%");
                        })
                        ->copyable()
                        ->toggleable(),
                ]),
                TextColumn::make('sgocCompany.name')
                    ->label(__('residence.sgoc_company_name'))
                    ->description(fn (Model $record): string => $record->sgocCompany->name_th ?? '-')
                    ->toggleable(),
                TextColumn::make('security_guard_count')
                    ->label(__('residence.number_of_security_guards'))
                    ->toggleable(),
                TextColumn::make('subscriptionExpires.expiry_date')
                    ->label(__('residence.sg_expiry_date'))
                    ->getStateUsing(function (Model $record) {
                        $subscription = $record->relationLoaded('subscriptionExpires')
                            ? $record->subscriptionExpires->firstWhere('type', 'Sgoc')
                            : $record->subscriptionExpires()->where('type', 'Sgoc')->first();

                        return $subscription?->expiry_date
                            ? Carbon::parse($subscription->expiry_date)->format('d-M-y')
                            : 'N/A';
                    })
                    ->toggleable(),
                TextColumn::make('bpo_software_suppliers.accounting')
                    ->label(__('residence.bpo_software_accounting_supplier'))
                    ->getStateUsing(function (Model $record) use ($suppliers) {
                        if (isset($record->bpo_software_suppliers['accounting'])) {
                            $id = $record->bpo_software_suppliers['accounting'];

                            return $suppliers[$id] ?? '-';
                        }

                        return '-';
                    })
                    ->searchable(query: fn ($query, $search) => BpoSoftwareSupplierFilterHelper::applyNameSearch($query, 'accounting', $search))
                    ->toggleable(),
                TextColumn::make('bpo_software_suppliers.vms')
                    ->label(__('residence.bpo_software_vms_supplier'))
                    ->getStateUsing(function (Model $record) use ($suppliers) {
                        if (isset($record->bpo_software_suppliers['vms'])) {
                            $id = $record->bpo_software_suppliers['vms'];

                            return $suppliers[$id] ?? '-';
                        }

                        return '-';
                    })
                    ->searchable(query: fn ($query, $search) => BpoSoftwareSupplierFilterHelper::applyNameSearch($query, 'vms', $search))
                    ->toggleable(),
                TextColumn::make('bpo_software_suppliers.apps_user')
                    ->label(__('residence.bpo_apps_user_supplier'))
                    ->getStateUsing(function (Model $record) use ($suppliers) {
                        if (isset($record->bpo_software_suppliers['apps_user'])) {
                            $id = $record->bpo_software_suppliers['apps_user'];

                            return $suppliers[$id] ?? '-';
                        }

                        return '-';
                    })
                    ->searchable(query: fn ($query, $search) => BpoSoftwareSupplierFilterHelper::applyNameSearch($query, 'apps_user', $search))
                    ->toggleable(),
            ])
            ->defaultSort('residences.updated_at', 'desc')
            ->recordActions([])
            ->filters([
                Filter::make('residence_filters')
                    ->columnSpan(3)
                    ->schema([
                        Fieldset::make(__('app.residence'))
                            ->columns(3)
                            ->schema([
                                Select::make('sub_type')
                                    ->label(__('residence.house_type'))
                                    ->options(collect(MoobanType::PUBLIC->allowedSubTypes())
                                        ->mapWithKeys(fn ($subType) => [$subType->value => $subType->getLabel()])
                                        ->toArray())
                                    ->multiple(),
                                Select::make('name')
                                    ->label(__('app.residence_mooban'))
                                    ->options(fn (): array => ResidenceOptionsSupport::visibleResidenceOptions($user))
                                    ->searchable(),

                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                ! empty($data['sub_type']),
                                fn (Builder $query) => $query->whereIn('residences.sub_type', (array) $data['sub_type'])
                            )
                            ->when(
                                $data['name'] ?? null,
                                fn (Builder $query): Builder => $query->whereId($data['name']),
                            )
                            ->when(
                                ! empty($data['property_management_type']),
                                fn (Builder $query) => $query->whereIn('residences.property_management_type', (array) $data['property_management_type']),
                            );
                    }),
                ResidenceLocationFilterSupport::makeProvinceFilters(
                    disableDistrictUntilProvince: true,
                    disableSubdistrictUntilDistrict: true,
                ),
                Filter::make('status_filters')
                    ->label(__('app.status'))
                    ->visible(fn () => $user?->hasAnyRole([RoleType::SUPER_ADMIN->value, RoleType::ADMIN->value]) ?? false)
                    ->columnSpan(3)
                    ->schema([
                        Fieldset::make(__('residence.filter_activation_status'))
                            ->columns(2)
                            ->schema([
                                Select::make('residence_activation_status_id')
                                    ->label(__('app.status'))
                                    ->options($activationStatusOptions)
                                    ->multiple(),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                ! empty($data['residence_activation_status_id']),
                                fn (Builder $query) => $query->whereIn('residences.residence_activation_status_id', $data['residence_activation_status_id'])
                            );
                    }),
                Filter::make('date_filters')
                    ->label(__('app.dates_filtering'))
                    ->visible(fn () => $user?->hasAnyRole([RoleType::SUPER_ADMIN->value, RoleType::ADMIN->value]) ?? false)
                    ->columnSpan(3)
                    ->schema([
                        Fieldset::make(__('residence.filter_created_updated'))
                            ->columns(3)
                            ->schema([
                                DatePicker::make('created_from')
                                    ->label(__('app.created_from')),
                                DatePicker::make('created_until')
                                    ->label(__('app.created_until')),
                                DatePicker::make('updated_from')
                                    ->label(__('app.last_updated_from')),
                                DatePicker::make('updated_until')
                                    ->label(__('app.last_updated_until')),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                ! empty($data['created_from']),
                                fn (Builder $q) => $q->whereDate('residences.created_at', '>=', $data['created_from'])
                            )
                            ->when(
                                ! empty($data['created_until']),
                                fn (Builder $q) => $q->whereDate('residences.created_at', '<=', $data['created_until'])
                            )
                            ->when(
                                ! empty($data['updated_from']),
                                fn (Builder $q) => $q->whereDate('residences.updated_at', '>=', $data['updated_from'])
                            )
                            ->when(
                                ! empty($data['updated_until']),
                                fn (Builder $q) => $q->whereDate('residences.updated_at', '<=', $data['updated_until'])
                            );
                    }),
                Filter::make('companies')
                    ->columnSpan(3)
                    ->schema([
                        Fieldset::make(__('residence.filter_companies'))
                            ->columns(3)
                            ->schema([
                                Select::make('developer_id')
                                    ->label(__('app.developer_company'))
                                    ->searchable()
                                    ->options(CompanyHelper::getCompanyOptions(CompanyTypeEnum::DEVELOPER)),
                                Select::make('property_management_id')
                                    ->label(__('app.property_management_company'))
                                    ->searchable()
                                    ->options(CompanyHelper::getCompanyOptions(CompanyTypeEnum::PROPERTY_MANAGEMENT)),
                                Select::make('sgoc_company_id')
                                    ->label(__('user.security_guard_company'))
                                    ->searchable()
                                    ->options(CompanyHelper::getCompanyOptions(CompanyTypeEnum::SECURITY_GUARD)),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['developer_id'] ?? null, fn (Builder $query, $value) => $query->where('residences.developer_id', $value))
                            ->when($data['property_management_id'] ?? null, fn (Builder $query, $value) => $query->where('residences.property_management_id', $value))
                            ->when($data['sgoc_company_id'] ?? null, fn (Builder $query, $value) => $query->where('residences.sgoc_company_id', $value));
                    }),
                Filter::make('operations')
                    ->columnSpan(3)
                    ->schema([
                        Fieldset::make(__('residence.filter_operations_software'))
                            ->columns(3)
                            ->schema([
                                Select::make('property_management_type')
                                    ->label(__('residence.property_management_type'))
                                    ->options(PropertyManagementType::options())
                                    ->multiple()
                                    ->searchable(),
                                Select::make('security_guard_count')
                                    ->label(__('residence.number_of_security_guards'))
                                    ->options($securityGuardCountOptions)
                                    ->multiple()
                                    ->searchable(),
                                Select::make('software_vms_ids')
                                    ->label(__('residence.bpo_software_vms_supplier'))
                                    ->options($suppliers)
                                    ->multiple()
                                    ->searchable(),
                                Select::make('software_accounting_ids')
                                    ->label(__('residence.bpo_software_accounting_supplier'))
                                    ->options($suppliers)
                                    ->multiple()
                                    ->searchable(),
                                Select::make('software_apps_user_ids')
                                    ->label(__('residence.bpo_apps_user_supplier'))
                                    ->options($suppliers)
                                    ->multiple()
                                    ->searchable(),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                ! empty($data['property_management_type']),
                                fn (Builder $query) => $query->whereIn('residences.property_management_type', (array) $data['property_management_type'])
                            )
                            ->when(
                                ! empty($data['security_guard_count']),
                                fn (Builder $query) => $query->whereIn('residences.security_guard_count', array_map('intval', (array) $data['security_guard_count']))
                            )
                            ->when(
                                ! empty($data['software_vms_ids']),
                                fn (Builder $query) => BpoSoftwareSupplierFilterHelper::applyJsonFilter($query, 'vms', (array) $data['software_vms_ids'])
                            )
                            ->when(
                                ! empty($data['software_accounting_ids']),
                                fn (Builder $query) => BpoSoftwareSupplierFilterHelper::applyJsonFilter($query, 'accounting', (array) $data['software_accounting_ids'])
                            )
                            ->when(
                                ! empty($data['software_apps_user_ids']),
                                fn (Builder $query) => BpoSoftwareSupplierFilterHelper::applyJsonFilter($query, 'apps_user', (array) $data['software_apps_user_ids'])
                            );
                    }),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(3)
            ->toolbarActions([
                FilamentExportBulkAction::make('export')
                    ->fileName('Residence-Report')
                    ->disableAdditionalColumns()
                    ->disableCsv()
                    ->disablePdf()
                    ->disableFilterColumns()
                    ->action(function (Component $livewire) use ($user) {
                        $columns = FilamentTableExportSupport::visibleColumnNames($livewire);
                        $fileNameInput = $livewire->mountedActions[0]['data']['file_name'] ?? 'export';
                        $fileName = FilamentTableExportSupport::excelFileName($fileNameInput, 'export', true);
                        $selectedResidenceIds = FilamentTableExportSupport::selectedRecordIds($livewire);
                        $residences = Residence::whereIn('id', $selectedResidenceIds, 'and', false)->latest('id')->get();

                        FilamentTableExportSupport::recordExportAudit($user, 'exported residence records');

                        return Excel::download(new ResidenceExport($residences, $columns), $fileName);
                    }),
            ])
            ->paginated([10, 25, 50]);
    }

    private static function getPropertyManagementTypeColor(mixed $state)
    {
        $types = WidgetColorPalette::propertyManagementTypes();

        // Handle enum objects (backed enums) and scalar values
        if (is_object($state) && property_exists($state, 'value')) {
            $key = $state->value;
        } elseif (is_int($state) || (is_string($state) && ctype_digit($state))) {
            $key = (int) $state;
        } else {
            $key = (int) $state;
        }

        $hex = $types[$key]['color'] ?? '#64748B';

        return WidgetColorPalette::statColorFromHex($hex);
    }
}
