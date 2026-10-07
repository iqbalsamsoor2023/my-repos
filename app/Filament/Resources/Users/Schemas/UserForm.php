<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\User\Gender;
use App\Enums\UserFamily\Relationship;
use App\Filament\Resources\Units\RelationManagers\OwnersRelationManager;
use App\Filament\Resources\Units\RelationManagers\TenantsRelationManager;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\Country;
use App\Models\UnitUser;
use Closure;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('user.user'))
                    ->description(__('user.user_detail'))
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Fieldset::make(__('app.details'))
                            ->columnSpanFull()
                            ->schema([
                                TextInput::make('name')
                                    ->label(__('app.name'))
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('email')
                                    ->label(__('app.email'))
                                    ->email()
                                    ->unique(ignorable: fn(?Model $record): ?Model => $record)
                                    ->required()
                                    ->placeholder('user@mymooban.co.th')
                                    ->maxLength(255),
                                TextInput::make('password')
                                    ->label(__('user.password'))
                                    ->autocomplete(false)
                                    ->password()
                                    ->same('confirm_password')
                                    ->required(fn(Component $livewire): bool => $livewire instanceof CreateUser)
                                    ->minLength(8)
                                    ->maxLength(255)
                                    ->dehydrateStateUsing(fn($state) => $state ? Hash::make($state) : null)
                                    ->dehydrated(fn($state) => filled($state))
                                    ->reactive()
                                    ->visible(fn(Component $livewire): bool => $livewire instanceof CreateUser || $livewire instanceof EditUser),
                                TextInput::make('confirm_password')
                                    ->label(__('user.confirm_password'))
                                    ->password()
                                    ->minLength(8)
                                    ->dehydrated(false)
                                    ->hidden(fn(Get $get) => $get('password') == null),
                                TextInput::make('phone_no')
                                    ->label(__('app.phone_number'))
                                    ->placeholder('+66(000)000-00000')
                                    ->required(),
                                Textarea::make('address')
                                    ->label(__('app.address'))
                                    ->rows(3)
                                    ->maxLength(255),
                                DatePicker::make('date_of_birth')
                                    ->label(__('user.date_of_birth')),
                                Radio::make('gender')
                                    ->label(__('user.gender'))
                                    ->options([
                                        Gender::MALE->value => __('user.' . strtolower(Gender::MALE->name)),
                                        Gender::FEMALE->value => __('user.' . strtolower(Gender::FEMALE->name)),
                                    ])
                                    ->required(fn(?Model $record) => $record?->hasRole('Developer') == false),
                                DatePicker::make('is_community_head_verified')
                                    ->label(__('Community Head Verified At')),
                                Toggle::make('is_main_owner')
                                    ->label(__('user.is_main_owner'))
                                    ->inline(false)
                                    ->rules([
                                        function (Component $livewire, Get $get, $record) {
                                            return function (string $attribute, $value, Closure $fail) use ($get, $livewire, $record) {
                                                $check_is_main_owner_exist = UnitUser::where('unit_id', $livewire->ownerRecord->id)->where('is_main_owner', 1)->exists();

                                                if ($livewire->mountedTableAction == 'create') {
                                                    if ($check_is_main_owner_exist) {
                                                        if ($get('is_main_owner') == true) {
                                                            $fail('This unit already has Main Owner!');
                                                        }
                                                    }
                                                } else {
                                                    $resident = UnitUser::where('unit_id', $record->pivot_unit_id)->where('user_id', $record->pivot_user_id)->first();

                                                    if ($check_is_main_owner_exist) {
                                                        if ($resident->is_main_owner != true) {
                                                            if ($get('is_main_owner') == true) {
                                                                $fail('This unit already has Main Owner!');
                                                            }
                                                        }
                                                    }
                                                }
                                            };
                                        },
                                    ])
                                    ->visible(fn(Component $livewire): bool => $livewire instanceof OwnersRelationManager),
                                Toggle::make('is_main_tenant')
                                    ->label(__('user.is_main_tenant'))
                                    ->inline(false)
                                    ->rules([
                                        function (Component $livewire, Get $get, $record) {
                                            return function (string $attribute, $value, Closure $fail) use ($get, $livewire, $record) {
                                                $check_is_main_tenant_exist = UnitUser::where('unit_id', $livewire->ownerRecord->id)->where('is_main_tenant', 1)->exists();

                                                if ($livewire->mountedTableAction == 'create') {
                                                    if ($check_is_main_tenant_exist) {
                                                        if ($get('is_main_tenant') == true) {
                                                            $fail('This unit already has Main Tenant!');
                                                        }
                                                    }
                                                } else {
                                                    $resident = UnitUser::where('unit_id', $record->pivot_unit_id)->where('user_id', $record->pivot_user_id)->first();

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
                                    ->visible(fn(Component $livewire): bool => $livewire instanceof TenantsRelationManager),
                                Select::make('relationship')
                                    ->label(__('app.relationship'))
                                    ->options(Relationship::options())
                                    ->searchable()
                                    ->visible(fn(Component $livewire): bool => $livewire instanceof OwnersRelationManager || $livewire instanceof TenantsRelationManager),

                                Fieldset::make(__('user.nationality'))
                                    ->columnSpanFull()
                                    ->schema([
                                        Select::make('country_id')
                                            ->label(__('user.nationality'))
                                            ->relationship('country', 'name', function () {
                                                return Country::orderBy('id');
                                            })
                                            ->searchable()
                                            ->required()
                                            ->reactive(),
                                        TextInput::make('passport_number')
                                            ->label(__('user.passport_number'))
                                            ->minLength(1)
                                            ->maxLength(20)
                                            ->hidden(fn(Get $get) => $get('country_id') == 1 || $get('country_id') == null),
                                        DatePicker::make('passport_expiry')
                                            ->hidden(fn(Get $get) => $get('country_id') == 1 || $get('country_id') == null),
                                        TextInput::make('id_number')
                                            ->label(__('user.id_number'))
                                            ->numeric()
                                            ->integer()
                                            ->hidden(fn(Get $get) => $get('country_id') != 1 || $get('country_id') == null)
                                            ->required(fn(?Model $record) => $record?->hasRole('Developer') == false),
                                    ]),
                            ]),
                    ]),

                Section::make(__('user.user_roles'))
                    ->description(__('user.roles'))
                    ->columnSpanFull()
                    ->schema([
                        Fieldset::make(__('user.assign_roles'))
                            ->schema([
                                CheckboxList::make('roles')
                                    ->label(__('user.roles'))
                                    ->relationship('roles', 'name')
                                    ->required()
                                    ->rules([
                                        function (Component $livewire) {
                                            return function (string $attribute, $value, Closure $fail) {
                                                $currentUser = auth()->user();

                                                if (in_array(1, $value)) {
                                                    // Only users with Super Admin role can assign it
                                                    if (! $currentUser || ! $currentUser->hasRole('Super Admin')) {
                                                        $fail('You are not authorized to assign the Super Admin role.');
                                                    }
                                                }
                                            };
                                        },
                                    ]),
                            ]),
                    ])
                    ->visible(fn(Component $livewire): bool => $livewire instanceof CreateUser || $livewire instanceof EditUser),
            ]);
    }
}
