<?php

namespace App\Filament\Resources\Residences\Schemas;

use App\Enums\Company\CompanyTypeEnum;
use App\Enums\Company\InsuranceTypeEnum;
use App\Enums\Residence\BpoSoftwareEnum;
use App\Enums\Residence\MoobanType;
use App\Enums\Residence\PropertyManagementType;
use App\Models\Company;
use App\Models\Erp\CdpCompany;
use App\Models\Erp\Contact;
use App\Support\ResidenceOptionsSupport;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class OperationsDetailsForm
{
    public static function getForm(): array
    {
        $setSecurityGuardContact = static function (Set $set, $state, ...$unused): void {
            if (empty($state)) {
                $set('securityGuard.contact_details.name', null);
                $set('securityGuard.contact_details.contact', null);

                return;
            }

            $contact = Contact::query()
                ->where('contactable_id', $state)
                ->where('contactable_type', 'App\\Models\\CdpCompany')
                ->first();

            if (! $contact) {
                $set('securityGuard.contact_details.name', null);
                $set('securityGuard.contact_details.contact', null);

                return;
            }

            $details = (array) $contact->contact_details;

            $set('securityGuard.contact_details.name', $details['name'] ?? null);
            $set('securityGuard.contact_details.contact', $details['mobile_number'] ?? null);
        };

        return [
            Grid::make(2)
                ->columnSpanFull()
                ->schema([
                    Fieldset::make(__('residence.company_and_pic_info_residence_only'))
                        ->columnSpanFull()
                        ->schema([
                            Select::make('company_id')
                                ->label(__('app.company'))
                                ->relationship('company', 'name', fn(Builder $query) => $query->where('type', 'Residence'))
                                ->searchable()
                                ->preload()
                                ->reactive()
                                ->required()
                                ->createOptionForm([
                                    Fieldset::make(__('residence.company_info'))
                                        ->columnSpanFull()
                                        ->schema([
                                            Hidden::make('type')
                                                ->default('Residence'),
                                            TextInput::make('name')
                                                ->label(__('app.company_name_en'))
                                                ->unique(ignorable: fn(?Model $record): ?Model => $record)
                                                ->required()
                                                ->maxLength(255),
                                            TextInput::make('contact_number')
                                                ->label(__('app.phone_number'))
                                                ->required()
                                                ->placeholder('+66(000)000-00000'),
                                            Textarea::make('address')
                                                ->label(__('app.address'))
                                                ->required(),
                                        ]),
                                    Fieldset::make(__('residence.residence_manager_pic'))
                                        ->columnSpanFull()
                                        ->schema([
                                            Repeater::make('person_in_charges')
                                                ->label('')
                                                ->schema([
                                                    TextInput::make('name')
                                                        ->label(__('app.name'))
                                                        ->reactive()
                                                        ->required(),
                                                    TextInput::make('email')
                                                        ->label(__('app.email'))
                                                        ->email()
                                                        ->placeholder('user@mymooban.com')
                                                        ->maxLength(255)
                                                        ->required(),
                                                    TextInput::make('contact')
                                                        ->label(__('app.phone_number'))
                                                        ->placeholder('+66(000)000-00000')
                                                        ->required(),
                                                ])
                                                ->columns(3)
                                                ->minItems(1)
                                                ->maxItems(3)
                                                ->reorderable(),
                                        ])
                                        ->columns(1),
                                ])
                                ->afterStateUpdated(function (Set $set, $state) {
                                    $company = Company::query()->find($state);

                                    if (! $company) {
                                        $set('company_contact_number', null);
                                        $set('company_address', null);
                                        $set('company.person_in_charges.name', []);
                                        $set('company.person_in_charges.email', []);
                                        $set('company.person_in_charges.contact', []);

                                        return;
                                    }

                                    $set('company_contact_number', $company->contact_number);
                                    $set('company_address', $company->address);

                                    $personInCharges = is_array($company->person_in_charges)
                                        ? $company->person_in_charges
                                        : [];

                                    $set('company.person_in_charges.name', array_column($personInCharges, 'name'));
                                    $set('company.person_in_charges.email', array_column($personInCharges, 'email'));
                                    $set('company.person_in_charges.contact', array_column($personInCharges, 'contact'));
                                })
                                ->saveRelationshipsUsing(null),
                            TextInput::make('company_contact_number')
                                ->label(__('app.company_contact_number'))
                                ->placeholder('+66(000)000-00000')
                                ->disabled(),
                            Textarea::make('company_address')
                                ->label(__('app.company_address'))
                                ->disabled(),
                            Fieldset::make(__('app.person_in_charge'))
                                ->columnSpanFull()
                                ->schema([
                                    TextInput::make('company.person_in_charges.name')
                                        ->label(__('app.name'))
                                        ->disabled(),
                                    TextInput::make('company.person_in_charges.email')
                                        ->label(__('app.email'))
                                        ->disabled(),
                                    TextInput::make('company.person_in_charges.contact')
                                        ->label(__('app.phone_number'))
                                        ->disabled(),
                                ]),
                        ])
                        ->reactive()
                        ->visible(fn(Get $get) => $get('mooban_type') == MoobanType::RESIDENCE->value),
                    Fieldset::make(__('residence.property_management'))
                        ->columnSpanFull()
                        ->schema([
                            Select::make('property_management_type')
                                ->label(__('residence.property_management_type'))
                                ->options(PropertyManagementType::options())
                                ->searchable()
                                ->afterStateUpdated(function (Set $set, $state) {
                                    // Always clear selected company when type changes
                                    $set('property_management_id', null);

                                    // If switched to Abandoned, clear all related PM fields
                                    if ($state === PropertyManagementType::ABANDONED->value) {
                                        $set('expiry_date', null);
                                        $set('juristic_details', null);
                                        $set('person_in_charges', []);
                                    }
                                })
                                ->reactive()
                                ->required(),
                            Select::make('property_management_id')
                                ->label(__('residence.property_management'))
                                ->options(function ($state) {
                                    return CdpCompany::query()
                                        ->whereHas('businessEntity', function ($query) {
                                            $query->where('business_category_id', CompanyTypeEnum::PROPERTY_MANAGEMENT->value);
                                        })
                                        ->when(
                                            filled($state),
                                            fn ($query) => $query->orWhere('id', $state)
                                        )
                                        ->with('businessEntity:id,name,name_th')
                                        ->get(['id', 'business_entity_id'])
                                        ->mapWithKeys(function ($company) {
                                            return [
                                                $company->id =>
                                                    ($company->businessEntity?->name ?? '') .
                                                    ' (' .
                                                    ($company->businessEntity?->name_th ?? '') .
                                                    ')'
                                            ];
                                        });
                                })
                                ->preload()
                                ->searchable()
                                ->reactive()
                                ->required()
                                ->hidden(
                                    fn(Get $get) => $get('property_management_type') == PropertyManagementType::ABANDONED->value
                                        || $get('property_management_type') == PropertyManagementType::NO_INFO->value
                                        || $get('property_management_type') == PropertyManagementType::PERSONAL_PROPERTY_MANAGEMENT->value
                                        || $get('property_management_type') == null
                                ),
                            DatePicker::make('expiry_date')
                                ->label(__('residence.expiry_date'))
                                ->format('Y-m-d')
                                ->required()
                                ->hidden(
                                    fn(Get $get) => $get('property_management_type') == PropertyManagementType::ABANDONED->value
                                        || $get('property_management_type') == PropertyManagementType::PERSONAL_PROPERTY_MANAGEMENT->value
                                        || $get('property_management_type') == null
                                ),
                            Fieldset::make(__('residence.juristic_person_details'))
                                ->columnSpanFull()
                                ->schema([
                                    TextInput::make('juristic_details.name')
                                        ->label(__('app.name'))
                                        ->required()
                                        ->maxLength(255)
                                        ->autocomplete(false),
                                    TextInput::make('juristic_details.email')
                                        ->label(__('app.email'))
                                        ->email()
                                        ->placeholder('juristic@mymooban.com')
                                        ->maxLength(255)
                                        ->required()
                                        ->autocomplete('email'),
                                    TextInput::make('juristic_details.phone_no')
                                        ->label(__('app.phone_number'))
                                        ->tel()
                                        ->placeholder('+66(000)000-00000')
                                        ->required()
                                        ->maxLength(20)
                                        ->regex('/^[+]?[0-9()\-\s]+$/')
                                        ->autocomplete('tel'),
                                    TextInput::make('juristic_details.line_id')
                                        ->label(__('user.line_id'))
                                        ->placeholder('Line ID')
                                        ->maxLength(50)
                                        ->autocomplete(false),
                                ])
                                ->columns(2)
                                ->hidden(fn(Get $get) => $get('property_management_type') == PropertyManagementType::ABANDONED->value || $get('property_management_type') == null),
                            Fieldset::make(__('app.person_in_charge'))
                                ->columnSpanFull()
                                ->schema([
                                    Repeater::make('person_in_charges')
                                        ->label('')
                                        ->schema([
                                            TextInput::make('name')
                                                ->label(__('app.name'))
                                                ->required()
                                                ->maxLength(255),
                                            TextInput::make('email')
                                                ->label(__('app.email'))
                                                ->email()
                                                ->placeholder('user@gmail.com')
                                                ->maxLength(255)
                                                ->required(),
                                            TextInput::make('contact')
                                                ->label(__('app.phone_number'))
                                                ->tel()
                                                ->placeholder('+66(000)000-00000')
                                                ->required()
                                                ->maxLength(20),
                                            TextInput::make('line_id')
                                                ->label(__('user.line_id'))
                                                ->maxLength(50),
                                        ])
                                        ->columns(4)
                                        ->minItems(0)
                                        ->maxItems(3)
                                        ->reorderable()
                                        ->defaultItems(0)
                                        ->columnSpanFull(),
                                ])
                                ->hidden(fn(Get $get) => $get('property_management_type') == PropertyManagementType::ABANDONED->value || $get('property_management_type') == null),
                        ])
                        ->reactive()
                        ->hidden(fn(Get $get) => $get('mooban_type') == MoobanType::RESIDENCE->value),
                    Fieldset::make(__('residence.security_guard'))
                        ->columnSpanFull()
                        ->schema([
                            Select::make('sgoc_company_id')
                                ->label(__('residence.security_guard'))
                                ->options(
                                    fn(): array => CdpCompany::query()
                                        ->with('businessEntity')
                                        ->whereNull('deleted_at')
                                        ->whereHas(
                                            'businessEntity',
                                            fn(Builder $query) =>
                                            $query->where('business_category_id', CompanyTypeEnum::SECURITY_GUARD->value)
                                        )
                                        ->get()
                                        ->sortBy(fn($company) => $company->businessEntity?->name)
                                        ->mapWithKeys(fn($company) => [
                                            $company->id => $company->businessEntity?->name,
                                        ])
                                        ->toArray()
                                )
                                ->afterStateUpdated($setSecurityGuardContact)
                                ->afterStateHydrated($setSecurityGuardContact)
                                ->reactive()
                                ->required()
                                ->searchable(),
                            DatePicker::make('sg_expiry_date')
                                ->label(__('residence.security_guard_expiry_date'))
                                ->format('Y-m-d')
                                ->required(),
                            TextInput::make('security_guard_count')
                                ->label(__('residence.number_of_security_guards'))
                                ->numeric()
                                ->default(0)
                                ->rules(['integer', 'min:0', 'max:30'])
                                ->step(1)
                                ->required(),
                            Fieldset::make(__('app.person_in_charges'))
                                ->columnSpanFull()
                                ->schema([
                                    TextInput::make('securityGuard.contact_details.name')
                                        ->label(__('app.name'))
                                        ->disabled(),
                                    TextInput::make('securityGuard.contact_details.contact')
                                        ->label(__('app.phone_number'))
                                        ->disabled(),
                                ]),
                        ]),
                    // Forms\Components\Fieldset::make(__('residence.technician'))
                    //     ->visible(fn (LivewireComponent $livewire): bool => $livewire instanceof Pages\EditResidence)
                    //     ->schema([
                    //         Forms\Components\Select::make('technician_id')
                    //             ->label(__('residence.technician_company'))
                    //             ->relationship('technicianCompany', 'name', fn (Builder $query) => $query->where('type', '=', 'Technician'))
                    //             ->searchable()
                    //             ->preload()
                    //             ->reactive()
                    //             ->required()
                    //             ->afterStateUpdated(function (Closure $set, $state) {
                    //                 $company = Company::whereId($state)->first();

                    //                 if (is_null($company) == false) {
                    //                     $set('company_contact_number', $company->contact_number);
                    //                     $set('company_address', $company->address);
                    //                 }

                    //                 if (is_null($company->person_in_charges) == false) {
                    //                     $set('technician.person_in_charges.name', array_column($company->person_in_charges, 'name'));
                    //                     $set('technician.person_in_charges.email', array_column($company->person_in_charges, 'email'));
                    //                     $set('technician.person_in_charges.contact', array_column($company->person_in_charges, 'contact'));
                    //                 }
                    //             })
                    //             ->saveRelationshipsUsing(null),
                    //         Forms\Components\DatePicker::make('technician_expiry_date')
                    //             ->label(__('residence.technician_expiry_date'))
                    //             ->format('Y-m-d')
                    //             ->required()
                    //             ->reactive(),
                    //         Forms\Components\Fieldset::make(__('app.person_in_charges'))
                    //             ->schema([
                    //                 Forms\Components\TextInput::make('technician.person_in_charges.name')
                    //                     ->label(__('app.name'))
                    //                     ->disabled(),
                    //                 Forms\Components\TextInput::make('technician.person_in_charges.email')
                    //                     ->label(__('app.email'))
                    //                     ->disabled(),
                    //                 Forms\Components\TextInput::make('technician.person_in_charges.contact')
                    //                     ->label(__('app.phone_number'))
                    //                     ->disabled(),
                    //             ]),
                    //     ]),
                    Fieldset::make(__('residence.fire_insurance_contract_info'))
                        ->columnSpanFull()
                        ->schema([
                            Select::make('insurance_company_id')
                                ->label(__('residence.company_insurance'))
                                ->relationship(
                                    'insuranceCompany',
                                    'name',
                                    fn(Builder $query) => $query->where('type', InsuranceTypeEnum::HOUSE_INSURANCE->value)
                                )
                                ->searchable()
                                ->preload()
                                ->reactive()
                                ->required(),
                            DatePicker::make('insurance_expiry_date')
                                ->label(__('residence.insurance_expiry_date'))
                                ->format('Y-m-d')
                                ->required(),
                        ]),
                    Fieldset::make(__('residence.bpo_software'))
                        ->columnSpanFull()
                        ->schema([
                            Select::make('bpo_software_suppliers.accounting')
                                ->label(__('residence.bpo_software_accounting_supplier'))
                                ->options(fn(): array => ResidenceOptionsSupport::bpoSupplierOptions(BpoSoftwareEnum::SOFTWARE_ACCOUNTING->value))
                                ->searchable()
                                ->preload(),
                            Select::make('bpo_software_suppliers.vms')
                                ->label(__('residence.bpo_software_vms_supplier'))
                                ->options(fn(): array => ResidenceOptionsSupport::bpoSupplierOptions(BpoSoftwareEnum::SOFTWARE_VMS->value))
                                ->searchable()
                                ->preload(),
                            Select::make('bpo_software_suppliers.apps_user')
                                ->label(__('residence.bpo_apps_user_supplier'))
                                ->options(fn(): array => ResidenceOptionsSupport::bpoSupplierOptions(BpoSoftwareEnum::APPS_USER->value))
                                ->searchable()
                                ->preload(),
                            DatePicker::make('bpo_software_suppliers.date')
                                ->label(__('residence.agm_date'))
                                ->displayFormat('Y-m-d')
                                ->reactive()
                                ->visible(fn(Get $get) => $get('mooban_type') == MoobanType::PUBLIC->value),
                        ]),
                ]),
        ];
    }
}
