<?php

namespace App\Filament\Resources\Residences\Schemas;

use App\Enums\Residence\ReportFortmat;
use App\Filament\Resources\Residences\Pages\EditResidence;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;
use Livewire\Component as LivewireComponent;

class ModulesAndFeaturesSubscriptionForm
{
    public static function getForm(): array
    {
        return [
            Grid::make(2)
                ->columnSpanFull()
                ->schema([
                    Fieldset::make(__('residence.account'))
                        ->visible(fn (LivewireComponent $livewire): bool => $livewire instanceof EditResidence)
                        ->columnSpanFull()
                        ->schema([
                            TextInput::make('property_management_user_id')
                                ->label(__('residence.property_management'))
                                ->disabled()
                                ->reactive()
                                ->hidden(fn (LivewireComponent $livewire): bool => ! $livewire->data['activeFeatures']['property_management']),
                            TextInput::make('sgoc_residence_security_guard_user_id')
                                ->label(__('residence.security_guard'))
                                ->disabled()
                                ->reactive()
                                ->hidden(fn (LivewireComponent $livewire): bool => ! $livewire->data['activeFeatures']['security_management']),
                            TextInput::make('receptionist_user_id')
                                ->label(__('residence.receptionist'))
                                ->disabled()
                                ->reactive()
                                ->hidden(fn (LivewireComponent $livewire): bool => ! $livewire->data['activeFeatures']['receptionist']),

                            // SLOW ISSUE. NEED TO FIX
                            // Forms\Components\Select::make('accountant_user_id')
                            //     ->label(__('Accountant'))
                            //     ->relationship('accountant', 'email')
                            //     ->disabled()
                            //     ->reactive()
                            //     ->hidden(fn (LivewireComponent $livewire): bool => ResidenceFeature::where('residence_id', $livewire->record->id)->where('feature_id', Features::ACCOUNTING_MANAGEMENT->value)->where('is_active', true)->first() == null),
                            //     ->label(__('Sales Management'))
                            //     ->relationship('salesManagementUser', 'email')
                            //     ->disabled()
                            //     ->reactive()
                            //     ->hidden(fn (LivewireComponent $livewire): bool => ResidenceFeature::where('residence_id', $livewire->record->id)->where('feature_id', Features::SALES_MANAGEMENT->value)->where('is_active', true)->first() == null),
                            // Forms\Components\Select::make('rtm_user_id')
                            //     ->label(__('Re-Sale & Tenancy Management'))
                            //     ->relationship('rtmUser', 'email')
                            //     ->disabled()
                            //     ->reactive()
                            //     ->hidden(fn (LivewireComponent $livewire): bool => ResidenceFeature::where('residence_id', $livewire->record->id)->where('feature_id', Features::RESALE_AND_TENANCY->value)->where('is_active', true)->first() == null),
                            // Forms\Components\Select::make('technician_user_id')
                            //     ->label(__('Facilities Management'))
                            //     ->relationship('technicianUser', 'email')
                            //     ->disabled()
                            //     ->reactive()
                            //     ->hidden(fn (LivewireComponent $livewire): bool => ResidenceFeature::where('residence_id', $livewire->record->id)->where('feature_id', Features::FACILITIES_MANAGEMENT->value)->where('is_active', true)->first() == null),
                            // Forms\Components\Select::make('maid_user_id')
                            //     ->label(__('Maid Management'))
                            //     ->relationship('maidUser', 'email')
                            //     ->disabled()
                            //     ->reactive()
                            //     ->hidden(fn (LivewireComponent $livewire): bool => ResidenceFeature::where('residence_id', $livewire->record->id)->where('feature_id', Features::CLEANLINESS_MANAGEMENT->value)->where('is_active', true)->first() == null),
                        ]),
                    Fieldset::make(__('residence.module_subscriptions'))
                        ->columnSpanFull()
                        ->schema([
                            self::toggle('residenceFeatures.salesManagement', __('residence.sales_management')),
                            self::toggle('residenceFeatures.resaleAndTenancy', __('residence.resale_tenancy_management')),
                            self::toggle('residenceFeatures.propertyManagement', __('residence.property_management'), true),
                            self::toggle('residenceFeatures.securityManagement', __('residence.security_management'), true),
                            self::toggle('residenceFeatures.facilitiesManagement', __('residence.facility_management')),
                            self::toggle('residenceFeatures.accountingManagement', __('residence.accounting_management')),
                            self::toggle('residenceFeatures.receptionManagement', __('residence.reception_management')),
                            self::toggle('residenceFeatures.cleanlinessManagement', __('residence.cleanliness_management')),
                        ]),
                    Fieldset::make(__('residence.user_app_features'))
                        ->columnSpanFull()
                        ->schema([
                            self::toggle('residenceFeatures.inbox', __('residence.inbox'), true),
                            self::toggle('residenceFeatures.visitor', __('visitor.visitor'), true),
                            self::toggle('residenceFeatures.claim', __('maintenance.claim'), true),
                            self::toggle('residenceFeatures.parcel', __('parcel.parcel'), true),
                            self::toggle('residenceFeatures.booking', __('app.booking'), true),
                            self::toggle('residenceFeatures.developer', __('app.developer'), true),
                            self::toggle('residenceFeatures.contact', __('app.contact'), true),
                            self::toggle('residenceFeatures.billing', __('app.billing'), true),
                            self::toggle('support_ticket_status', __('support-ticket.support_ticket_status'), true),

                            // Mutually exclusive parking toggles
                            self::toggle(
                                'residenceFeatures.parkingFeeBasic',
                                __('app.parking_fees_basic'),
                                true,
                                true,
                                function (Set $set, $state) {
                                    if ($state) {
                                        $set('residenceFeatures.parkingFeePro', false);
                                    }
                                }
                            ),
                            self::toggle(
                                'residenceFeatures.parkingFeePro',
                                __('app.parking_fees_pro'),
                                false,
                                true,
                                function (Set $set, $state) {
                                    if ($state) {
                                        $set('residenceFeatures.parkingFeeBasic', false);
                                    }
                                }
                            ),
                            Radio::make('report_format')
                                ->label(__('residence.report_format'))
                                ->options([
                                    ReportFortmat::EXCEL->value => Str::title(ReportFortmat::EXCEL->name),
                                    ReportFortmat::PDF->value => Str::upper(ReportFortmat::PDF->name),
                                ])
                                ->default(ReportFortmat::EXCEL->value)
                                ->inline(),
                        ]),
                ]),
        ];
    }

    /**
     * Helper to create a toggle that always submits boolean
     */
    protected static function toggle(string $name, string $label, bool $default = false, bool $reactive = false, ?\Closure $afterStateUpdated = null): Toggle
    {
        $toggle = Toggle::make($name)
            ->label($label)
            ->default($default)
            ->dehydrateStateUsing(fn ($state) => (bool) $state);

        if ($reactive) {
            $toggle->reactive();
        }

        if ($afterStateUpdated) {
            $toggle->afterStateUpdated($afterStateUpdated);
        }

        return $toggle;
    }
}
