<?php

namespace App\Filament\Resources\UnitUsers\Schemas;

use App\Enums\Company\InsuranceTypeEnum;
use App\Enums\GeneralStatus;
use App\Enums\UnitUser\ApprovalStatusType;
use App\Enums\User\Gender;
use App\Enums\UserFamily\Relationship;
use App\Filament\Resources\Units\RelationManagers\OwnersRelationManager;
use App\Filament\Resources\Units\RelationManagers\TenantsRelationManager;
use App\Filament\Resources\UnitUsers\Pages\CreateUnitUser;
use App\Filament\Resources\UnitUsers\Pages\EditUnitUser;
use App\Models\Country;
use App\Models\InsuranceCompany;
use App\Models\Unit;
use App\Models\UnitUser;
use App\Models\User;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\App;
use Livewire\Component;

class UnitUserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('app.resident'))
                    ->description(__('user.resident_detail'))
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Fieldset::make(__('unit.unit_assignment'))
                            ->columnSpanFull()
                            ->schema([
                                Select::make('residence_id')
                                    ->label(__('app.mooban_or_residence'))
                                    ->options(function (Component $livewire) {
                                        if ($livewire instanceof CreateUnitUser) {
                                            return list_create_residences();
                                        }

                                        return list_residences();
                                    })
                                    ->searchable()
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function (Set $set) {
                                        $set('unit_id', null);
                                        $set('user_id', null);
                                    })
                                    ->disabled(fn(Component $livewire): bool => $livewire instanceof EditUnitUser)
                                    ->hidden(fn(Component $livewire): bool => $livewire instanceof OwnersRelationManager || $livewire instanceof TenantsRelationManager),
                                Select::make('unit_id')
                                    ->label(__('unit.unit_number'))
                                    ->options(function (callable $get) {
                                        return Unit::where('residence_id', $get('residence_id'))->pluck('unit_number', 'id');
                                    })
                                    ->searchable()
                                    ->required()
                                    ->reactive()
                                    ->disabled(fn(Component $livewire): bool => $livewire instanceof EditUnitUser)
                                    ->hidden(fn(Component $livewire): bool => $livewire instanceof OwnersRelationManager || $livewire instanceof TenantsRelationManager),
                            ])
                            ->hidden(fn(Component $livewire): bool => $livewire instanceof OwnersRelationManager),

                        Fieldset::make(__('app.details'))
                            ->columnSpanFull()
                            ->schema([
                                Select::make('user_id')
                                    ->label(__('user.user'))
                                    ->getSearchResultsUsing(function (string $query, Get $get, Component $livewire) {
                                        $users = User::role(['Unit Owner', 'Unit Tenant'])
                                            ->where('email', 'like', "%{$query}%");
                                
                                        // CREATE: exclude users already in unit
                                        if ($livewire instanceof CreateUnitUser) {
                                            $users->whereNotIn('id', function ($sub) use ($get) {
                                                $sub->select('user_id')
                                                    ->from('unit_user')
                                                    ->where('unit_id', $get('unit_id'));
                                            });
                                        }
                                
                                        // EDIT: allow current user
                                        if ($livewire instanceof EditUnitUser) {
                                            $currentUserId = $livewire->record?->user_id;

                                            if ($currentUserId) {
                                                $users->where(function ($q) use ($get, $currentUserId) {
                                                    $q->whereNotIn('id', function ($sub) use ($get) {
                                                        $sub->select('user_id')
                                                            ->from('unit_user')
                                                            ->where('unit_id', $get('unit_id'));
                                                    })
                                                    ->orWhere('id', $currentUserId);
                                                });
                                            }
                                        }
                                
                                        return $users
                                            ->limit(10)
                                            ->pluck('email', 'id');
                                    })
                                    ->getOptionLabelUsing(fn ($value): ?string => User::find($value)?->email)
                                    ->searchable()
                                    ->searchDebounce(300)
                                    ->optionsLimit(15)
                                    ->required()
                                    ->reactive()
                                    ->disabled(fn(Component $livewire): bool => $livewire instanceof EditUnitUser || (($livewire->mountedActions[0]['name'] ?? null) === 'edit')),
                                Select::make('relationship')
                                    ->label(__('user.relationship'))
                                    ->options(
                                        collect(Relationship::cases())
                                            ->mapWithKeys(fn($case) => [$case->value => $case->label()])
                                            ->toArray()
                                    )
                                    ->searchable(),
                                Toggle::make('is_owner')
                                    ->label(__('user.is_owner'))
                                    ->inline(false)
                                    ->reactive()
                                    ->rules([
                                        function (Get $get, Component $livewire) {
                                            return function (string $attribute, $value, Closure $fail) use ($get, $livewire) {
                                    
                                                // Only enforce on CREATE
                                                if (! $livewire instanceof CreateUnitUser) {
                                                    return;
                                                }
                                    
                                                $userId = $get('user_id');
                                    
                                                if (! $userId) {
                                                    return;
                                                }
                                    
                                                $user = User::find($userId);
                                    
                                                if (! $user) {
                                                    return;
                                                }
                                    
                                                $isOwner = $user->hasRole('Unit Owner');
                                                $isTenant = $user->hasRole('Unit Tenant');
                                    
                                                // Owner-only cannot be tenant
                                                if ($isOwner && ! $isTenant && $value === false) {
                                                    $fail(__('user.owner_cannot_be_tenant'));
                                                }
                                    
                                                // Tenant-only cannot be owner
                                                if ($isTenant && ! $isOwner && $value === true) {
                                                    $fail(__('user.tenant_cannot_be_owner'));
                                                }                                    
                                            };
                                        },
                                    ])                                    
                                    ->disabled(fn(Component $livewire): bool => $livewire instanceof EditUnitUser)
                                    ->hidden(fn(Component $livewire): bool => $livewire instanceof OwnersRelationManager || $livewire instanceof TenantsRelationManager),
                                Toggle::make('is_main_owner')
                                    ->label(__('user.is_main_owner'))
                                    ->rules([
                                        function (Component $livewire, Get $get, $record) {
                                            return function (string $attribute, $value, Closure $fail) use ($get, $livewire, $record) {
                                                $unitId = $get('unit_id');
                                                $userId = $get('user_id');

                                                if ($livewire instanceof CreateUnitUser || (($livewire->mountedActions[0]['name'] ?? null) === 'create')) {
                                                    if (isset($livewire->mountedActions[0]['name'])) {
                                                        $unitId = $livewire->ownerRecord->id;
                                                    }else{
                                                        $unitId = $get('unit_id');
                                                    }

                                                    $check_is_main_owner_exist = UnitUser::where('unit_id', $unitId)->where('is_main_owner', 1)->exists();

                                                    if ($check_is_main_owner_exist) {
                                                        if ($get('is_main_owner') == true) {
                                                            $fail('This unit already has Main Owner!');
                                                        }
                                                    }
                                                } else {
                                                    $unitId = $record->unit_id ?? null;
                                                    $userId = $record->user_id ?? null;

                                                    if ($livewire instanceof EditUnitUser || isset($livewire->mountedActions)) {
                                                        $unitId = $record->unit_id;
                                                        $userId = $record->user_id;
                                                    }

                                                    if ($unitId && $userId) {
                                                        $existingMainOwner = UnitUser::where('unit_id', $unitId)
                                                            ->where('is_main_owner', 1)
                                                            ->exists();

                                                        $resident = UnitUser::where('unit_id', $unitId)
                                                            ->where('user_id', $userId)
                                                            ->first();

                                                        if ($existingMainOwner && $resident && ! $resident->is_main_owner && $get('is_main_owner')) {
                                                            $fail('This unit already has Main Owner!');
                                                        }
                                                    }
                                                }
                                            };
                                        },
                                    ])
                                    ->inline(false)
                                    ->reactive()
                                    ->visible(fn(Get $get, Component $livewire) => $get('is_owner') == true || $livewire instanceof OwnersRelationManager)
                                    ->hidden(fn(Component $livewire): bool => $livewire instanceof TenantsRelationManager),
                                Toggle::make('is_main_tenant')
                                    ->label(__('user.is_main_tenant'))
                                    ->visible(fn(Get $get) => $get('is_owner') == false)
                                    ->rules([
                                        function (Component $livewire,Get $get, $record) {
                                            return function (string $attribute, $value, Closure $fail) use ($get, $livewire, $record) {
                                                $unitId = $get('unit_id');
                                                $userId = $get('user_id');

                                                if ($livewire instanceof CreateUnitUser || (($livewire->mountedActions[0]['name'] ?? null) === 'create')) {
                                                    if (isset($livewire->mountedActions[0]['name'])) {
                                                        $unitId = $livewire->ownerRecord->id;
                                                    }else{
                                                        $unitId = $get('unit_id');
                                                    }

                                                    $check_is_main_tenant_exist = UnitUser::where('unit_id', $unitId)->where('is_main_tenant', 1)->exists();

                                                    if ($check_is_main_tenant_exist) {
                                                        if ($get('is_main_tenant') == true) {
                                                            $fail('This unit already has Main Tenant!');
                                                        }
                                                    }
                                                } else {
                                                    if (isset($livewire->mountedActions)) {
                                                        $unitId = $record->unit_id;
                                                        $userId = $record->user_id;
                                                    }

                                                    $check_is_main_tenant_exist = UnitUser::where('unit_id', $unitId)->where('is_main_tenant', 1)->exists();
                                                    $resident = UnitUser::where('unit_id', $unitId)->where('user_id', $userId)->first();

                                                    if ($check_is_main_tenant_exist) {
                                                        if ($resident->is_main_tenant != true) {
                                                            if ($get('is_main_tenant') == true) {
                                                                $fail('This unit already has Main Tenant!');
                                                            }
                                                        }
                                                    }
                                                }
                                            };
                                        },
                                    ])
                                    ->inline(false)
                                    ->reactive()
                                    ->hidden(fn(Get $get, Component $livewire) => $livewire instanceof OwnersRelationManager)
                            ]),

                        Fieldset::make(__('user.personal_information'))
                            ->columnSpanFull()
                            ->schema([
                                TextInput::make('phone_no_display')
                                    ->label(__('app.phone_number'))
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->formatStateUsing(fn ($record) => $record?->user?->phone_no ?? '-'),
                                DatePicker::make('date_of_birth_display')
                                    ->label(__('user.date_of_birth'))
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->formatStateUsing(fn ($record) => $record?->user?->date_of_birth ?? null),
                                Select::make('country_id_display')
                                    ->label(__('user.nationality'))
                                    ->options(fn () => Country::pluck('name', 'id'))
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->formatStateUsing(fn ($record) => $record?->user?->country_id ?? null),
                                TextInput::make('id_number_display')
                                    ->label(__('user.id_number'))
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->formatStateUsing(fn ($record) => $record?->user?->id_number ?? '-'),
                                Select::make('gender_display')
                                    ->label(__('user.gender'))
                                    ->options(
                                        collect(Gender::cases())
                                            ->mapWithKeys(fn($case) => [$case->value => $case->label()])
                                            ->toArray()
                                    )
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->formatStateUsing(fn ($record) => $record?->user?->gender ?? null),
                            ]),   

                        Fieldset::make(__('user.approval_status'))
                            ->columnSpanFull()
                            ->hidden(fn (Component $livewire): bool =>
                                $livewire instanceof CreateUnitUser
                                || (($livewire->mountedActions[0]['name'] ?? null) === 'create')
                            )
                            ->schema([
                                Select::make('approval_status')
                                    ->label(__('user.approval_status'))
                                    ->options([
                                        ApprovalStatusType::APPROVED->value => __('app.'.strtolower(ApprovalStatusType::APPROVED->name)),
                                        ApprovalStatusType::REJECTED->value => __('app.'.strtolower(ApprovalStatusType::REJECTED->name)),
                                    ])
                                    ->searchable()
                                    ->required(),
                                Select::make('email_status')
                                    ->label(__('user.email_status'))
                                    ->options([
                                        GeneralStatus::INACTIVE->value => __('app.pending'),
                                        GeneralStatus::ACTIVE->value => __('app.verified'),
                                    ])
                                    ->searchable(),
                            ]),

                        Fieldset::make(__('user.user_life_insurance_info'))
                            ->columnSpanFull()
                            ->schema([
                                Select::make('insurance_company_id')
                                    ->label(__('residence.company_insurance'))
                                    ->options(function () {
                                        $locale = App::getLocale();
                                        $nameField = $locale === 'th' ? 'name_th' : 'name';

                                        return InsuranceCompany::query()
                                            ->where('type', InsuranceTypeEnum::LIFE_INSURANCE->value)
                                            ->pluck($nameField, 'id');
                                    })
                                    ->disabled()            // visually and functionally readonly
                                    ->dehydrated(false),    // prevents it from submitting
                                TextInput::make('insurance_policy_no')
                                    ->label(__('user.insurance_cert_no'))
                                    ->disabled()
                                    ->readOnly()
                                    ->dehydrated(false), // prevents submitting if unchanged
                            ]),
                    ])
            ]);
    }
}
