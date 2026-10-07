<?php

namespace App\Filament\Resources\SaleManagements\Schemas;

use App\Enums\SalesManagement\ConstructionProgressEnum;
use App\Enums\SalesManagement\SellingStatusEnum;
use App\Enums\User\RoleType;
use App\Models\AvailablePayment;
use App\Models\SaleAdvertisement;
use App\Models\Unit;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class SaleManagementForm
{
    public static function configure(Schema $schema): Schema
    {
        $user = auth()->user();
        $userHasSmRole = $user->hasAnyRole([RoleType::SALES_MANAGEMENT->value]);
        $defaultResidenceId = $userHasSmRole && !empty(get_residence_id_list_by_sm($user->id)) ? get_residence_id_list_by_sm($user->id)[0] : null;

        return $schema
            ->components([
                Section::make(__('sale.sale_information'))
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Select::make('residence_id')
                            ->label(__('app.mooban_or_residence'))
                            ->options(list_residences())
                            ->searchable()
                            ->required()
                            ->live()
                            ->default($defaultResidenceId)
                            ->afterStateUpdated(fn(Set $set) => $set('unit_id', null))
                            ->hidden(fn() => $userHasSmRole),
                        Select::make('unit_id')
                            ->label(__('unit.unit_number'))
                            ->options(function (callable $get) {
                                $residenceId = $get('residence_id');
                                return $residenceId ? Unit::where('residence_id', $residenceId)->pluck('unit_number', 'id') : [];
                            })
                            ->searchable()
                            ->required()
                            ->rules([
                                fn(Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                    $existing = SaleAdvertisement::where('unit_id', $value)
                                        ->when($get('id'), fn($q, $id) => $q->where('id', '!=', $id))
                                        ->exists();
                                    if ($existing) {
                                        $fail(__('sale.unit_already_exists'));
                                    }
                                },
                            ]),
                        TextInput::make('sale_price')
                            ->label(__('sale.sale_price'))
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(999999999999),
                        Select::make('available_payments')
                            ->label(__('sale.available_payments'))
                            ->relationship('available_payments', app()->getLocale() === 'th' ? 'payment_name_th' : 'payment_name')
                            ->multiple()
                            ->searchable(),
                        Select::make('selling_status')
                            ->label(__('sale.selling_status'))
                            ->options(SellingStatusEnum::class)
                            ->searchable()
                            ->default(SellingStatusEnum::NOT_YET_SOLD)
                            ->required(),
                        Select::make('construction_progress')
                            ->label(__('unit.construction_progress'))
                            ->options(ConstructionProgressEnum::class)
                            ->searchable()
                            ->required(),
                        Toggle::make('is_active')
                            ->label(__('sale.active'))
                            ->inline(false)
                            ->default(true),
                    ])
            ]);
    }
}
