<?php

namespace App\Filament\Resources\TenancyManagements\Schemas;

use App\Enums\TenancyManagement\TenancyManagementStatus;
use App\Enums\User\RoleType;
use App\Filament\Resources\TenancyManagements\Pages\CreateTenancyManagement;
use App\Filament\Resources\TenancyManagements\TenancyManagementResource;
use App\Models\RentAdvertisement;
use App\Models\Residence;
use App\Models\Unit;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class TenancyManagementForm
{
    public static function configure(Schema $schema): Schema
    {
        $user = auth()->user();
        $userHasRtmRole = $user->hasAnyRole([RoleType::RESALES_AND_TENANCY_MANAGEMENT->value]);
        $defaultResidenceId = $userHasRtmRole && !empty(get_residence_id_list_by_rtm($user->id)) ? get_residence_id_list_by_rtm($user->id)[0] : null;

        return $schema
            ->components([
                Section::make(__('resales-and-tenancies.rent_information'))
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Select::make('residence_id')
                            ->label(__('resales-and-tenancies.residence') . '/' . __('resales-and-tenancies.mooban'))
                            ->options(list_residences())
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn(Set $set) => $set('unit_id', null))
                            ->default($defaultResidenceId)
                            ->hidden(fn() => $userHasRtmRole)
                            ->rules([fn(): Closure => function (string $attribute, $value, Closure $fail) {
                                if (!Residence::where('id', $value)->whereNotNull('rtm_user_id')->exists()) {
                                    $fail(__('The selected residence is not valid for tenancy management.'));
                                }
                            }]),
                        Select::make('unit_id')
                            ->label(__('resales-and-tenancies.unit_number'))
                            ->options(function (callable $get) {
                                $residenceId = $get('residence_id');
                                return $residenceId ? Unit::where('residence_id', $residenceId)->orderBy('unit_number')->pluck('unit_number', 'id') : [];
                            })
                            ->searchable()
                            ->required()
                            ->rules([fn(Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                $exists = RentAdvertisement::where('unit_id', $value)
                                    ->availableForRent()
                                    ->when($get('id'), fn($q, $id) => $q->where('id', '!=', $id))
                                    ->exists();

                                if ($exists) {
                                    $fail(__('The selected unit is already under rent.'));
                                }
                            }]),
                        DatePicker::make('rental_start_date')
                            ->label(__('resales-and-tenancies.rental_start_date'))
                            ->minDate(fn($livewire) => $livewire instanceof CreateTenancyManagement ? now()->toDateString() : null),
                        Select::make('tenancy_status')
                            ->label(__('resales-and-tenancies.tenancy_status'))
                            ->options(TenancyManagementStatus::class)
                            ->searchable()
                            ->default(TenancyManagementStatus::FOR_RENT)
                            ->required(),

                        Group::make([
                            Toggle::make('is_rental_upfront')
                                ->label(__('resales-and-tenancies.is_rental_upfront'))
                                ->default(false)
                                ->afterStateUpdated(fn(callable $get, callable $set) => self::recalculateTotal($set, $get))
                                ->inline(false)
                                ->live(),
                            Toggle::make('is_active')
                                ->label(__('resales-and-tenancies.active'))
                                ->inline(false)
                                ->default(true),
                        ])
                            ->columns(2),

                        Group::make([
                            TextInput::make('contract_months')
                                ->label(__('resales-and-tenancies.contract_months'))
                                ->numeric()
                                ->required(),

                            TextInput::make('rent_price')
                                ->label(__('resales-and-tenancies.rent_price'))
                                ->numeric()
                                ->required()
                                ->afterStateUpdated(fn(callable $get, callable $set) => TenancyManagementResource::recalculateTotal($set, $get))
                                ->live(onBlur: true)
                                ->columns(1),
                            TextInput::make('deposit')
                                ->label(__('resales-and-tenancies.deposit'))
                                ->numeric()
                                ->required()
                                ->afterStateUpdated(fn(callable $get, callable $set) => TenancyManagementResource::recalculateTotal($set, $get))
                                ->live(onBlur: true)
                                ->columns(1),
                            TextInput::make('total')
                                ->label(__('resales-and-tenancies.total'))
                                ->disabled()
                                ->dehydrated(false)
                                ->afterStateHydrated(fn(callable $set, callable $get) => TenancyManagementResource::recalculateTotal($set, $get))
                                ->columns(1)
                        ])
                            ->columns(4)
                            ->columnSpanFull(),

                        Toggle::make('has_custom_rule')
                            ->label(__('resales-and-tenancies.has_custom_rental_contract'))
                            ->default(false)
                            ->live()
                            ->columnSpanFull(),
                        Repeater::make('custom_rental_contracts')
                            ->addActionLabel(__('resales-and-tenancies.add_custom_rental_contracts'))
                            ->hiddenLabel()
                            ->visible(fn(Get $get): bool => $get('has_custom_rule'))
                            ->orderColumn(false)
                            ->schema([
                                TextInput::make('contract_months')
                                    ->label(__('resales-and-tenancies.contract_months'))
                                    ->numeric()
                                    ->required(),

                                TextInput::make('rent_price')
                                    ->label(__('resales-and-tenancies.rent_price'))
                                    ->numeric()
                                    ->required()
                                    ->afterStateUpdated(fn(callable $get, callable $set) => TenancyManagementResource::recalculateTotal($set, $get))
                                    ->live(onBlur: true),
                                TextInput::make('deposit')
                                    ->label(__('resales-and-tenancies.deposit'))
                                    ->numeric()
                                    ->required()
                                    ->afterStateUpdated(fn(callable $get, callable $set) => TenancyManagementResource::recalculateTotal($set, $get))
                                    ->live(onBlur: true),
                                TextInput::make('total')
                                    ->label(__('resales-and-tenancies.total'))
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->afterStateHydrated(fn(callable $set, callable $get) => TenancyManagementResource::recalculateTotal($set, $get))
                            ])
                            ->columns(4)
                            ->columnSpanFull()
                    ])
            ]);
    }
}
