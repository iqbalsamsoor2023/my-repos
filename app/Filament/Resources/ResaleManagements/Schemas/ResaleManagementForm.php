<?php

namespace App\Filament\Resources\ResaleManagements\Schemas;

use App\Enums\ResalesManagement\ResalesManagementStatus;
use App\Enums\SalesAndTenancies\BankLoanStatusEnum;
use App\Enums\User\RoleType;
use App\Models\ResaleAdvertisement;
use App\Models\Residence;
use App\Models\Unit;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class ResaleManagementForm
{
    public static function configure(Schema $schema): Schema
    {
        $user = auth()->user();
        $userHasRtmRole = $user->hasAnyRole([RoleType::RESALES_AND_TENANCY_MANAGEMENT->value]);
        $defaultResidenceId = $userHasRtmRole && !empty(get_residence_id_list_by_rtm($user->id)) ? get_residence_id_list_by_rtm($user->id)[0] : null;

        return $schema
            ->components([
                Section::make(__('resales-and-tenancies.resale_information'))
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Select::make('residence_id')
                            ->label(__('app.mooban_or_residence'))
                            ->options(list_residences())
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn(Set $set) => $set('unit_id', null))
                            ->default($defaultResidenceId)
                            ->hidden(fn() => $userHasRtmRole)
                            ->rules([fn(): Closure => function (string $attribute, $value, Closure $fail) {
                                if (!Residence::where('id', $value)->whereNotNull('rtm_user_id')->exists()) {
                                    $fail(__('The selected residence is not valid for resale management.'));
                                }
                            }]),
                        Select::make('unit_id')
                            ->label(__('unit.unit_number'))
                            ->options(function (callable $get) {
                                $residenceId = $get('residence_id');
                                return $residenceId ? Unit::where('residence_id', $residenceId)->orderBy('unit_number')->pluck('unit_number', 'id') : [];
                            })
                            ->searchable()
                            ->required()
                            ->rules([fn(Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                $exists = ResaleAdvertisement::where('unit_id', $value)
                                    ->availableForResale()
                                    ->when($get('id'), fn($q, $id) => $q->where('id', '!=', $id))
                                    ->exists();

                                if ($exists) {
                                    $fail(__('The selected unit is already under resale.'));
                                }
                            }]),
                        TextInput::make('resale_price')
                            ->label(__('resales-and-tenancies.resale_price'))
                            ->numeric(),
                        Select::make('bank_loan_status')
                            ->label(__('resales-and-tenancies.bank_loan_status'))
                            ->options(BankLoanStatusEnum::class)
                            ->searchable(),
                        Select::make('status')
                            ->label(__('resales-and-tenancies.resale_status'))
                            ->options(ResalesManagementStatus::class)
                            ->searchable()
                            ->default(ResalesManagementStatus::FOR_RESALE)
                            ->required(),
                        Toggle::make('have_ownership_documents')
                            ->label(__('resales-and-tenancies.have_ownership_documents'))
                            ->inline(false),
                        Toggle::make('is_active')
                            ->label(__('resales-and-tenancies.active'))
                            ->inline(false)
                            ->default(true),
                    ])
            ]);
    }
}
