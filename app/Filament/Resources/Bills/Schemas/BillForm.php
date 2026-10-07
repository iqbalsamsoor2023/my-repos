<?php

namespace App\Filament\Resources\Bills\Schemas;

use App\Enums\Bill\BillStatus;
use App\Enums\Bill\PaymentMode;
use App\Filament\Resources\Bills\Pages\CreateBill;
use App\Filament\Resources\Bills\Pages\EditBill;
use App\Filament\Resources\Bills\Pages\ViewBill;
use App\Models\Payment;
use App\Models\Residence;
use App\Models\Unit;
use App\Models\UnitUser;
use App\Models\User;
use App\Rules\BillSetting;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Livewire\Component;

class BillForm
{
    public static function configure(Schema $schema): Schema
    {
        $user = auth()->user();

        return $schema
            ->components([
                Section::make(__('menu.accountingManagement.manage_bill_reminders'))
                    ->description(__('billing.bill_reminder_details'))
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Fieldset::make(__('app.details'))
                            ->columnSpanFull()
                            ->schema([
                                Select::make('residence_id')
                                    ->label(__('app.mooban_or_residence'))
                                    ->options(function (Component $livewire) {
                                        if ($livewire instanceof CreateBill) {
                                            return list_create_residences();
                                        }

                                        return list_residences();
                                    })
                                    ->default(function () {
                                        $user = auth()->user();
                                        if ($user->hasRole('Property Management')) {
                                            return $user->propertyManagement?->id;
                                        }
                                        return null;
                                    })
                                    ->required()
                                    ->reactive()
                                    ->searchable()
                                    ->rules([new BillSetting()])
                                    ->visible(fn(Component $livewire): bool => $livewire instanceof CreateBill),
                                Select::make('residence_id')
                                    ->label(__('app.mooban_or_residence'))
                                    ->options(list_residences())
                                    ->dehydrated(false)
                                    ->disabled()
                                    ->searchable()
                                    ->visible(fn(Component $livewire): bool => $livewire instanceof EditBill),
                                Select::make('payer_unit_id')
                                    ->label(__('unit.unit_number'))
                                    ->options(function (callable $get) use ($user) {
                                        $residence_id = $get('residence_id');

                                        if ($user->hasRole('Property Management')) {
                                            $residence_id = Residence::where('property_management_user_id', $user->id)->pluck('id');
                                        }

                                        return Unit::where('residence_id', $residence_id)->pluck('unit_number', 'id');
                                    })
                                    ->required()
                                    ->searchable()
                                    ->visible(fn(Component $livewire): bool => $livewire instanceof CreateBill),
                                TextInput::make('unit_number')
                                    ->label(__('unit.unit_number'))
                                    ->disabled()
                                    ->visible(fn(Component $livewire): bool => $livewire instanceof EditBill || $livewire instanceof ViewBill),
                                TextInput::make('bill_no')
                                    ->label(__('billing.bill_no'))
                                    ->maxLength(100),
                                DatePicker::make('bill_date')
                                    ->label(__('billing.bill_date'))
                                    ->required()
                                    ->displayFormat('Y-m-d')
                                    ->disabled(fn(Component $livewire): bool => $livewire instanceof EditBill),
                                DatePicker::make('due_date')
                                    ->label(__('billing.due_date'))
                                    ->required()
                                    ->displayFormat('Y-m-d'),
                                Radio::make('status')
                                    ->label(__('app.status'))
                                    ->options([
                                        BillStatus::PAID->value => BillStatus::PAID->getLabel(),
                                        BillStatus::UNPAID->value => BillStatus::UNPAID->getLabel(),
                                        BillStatus::CANCEL->value => BillStatus::CANCEL->getLabel(),
                                    ])
                                    ->columns(5)
                                    ->required()
                                    ->reactive()
                                    ->visible(fn(Component $livewire): bool => $livewire instanceof EditBill || $livewire instanceof ViewBill),
                                DateTimePicker::make('transaction_datetime')
                                    ->label(__('billing.payment_datetime'))
                                    ->reactive()
                                    ->hidden()
                                    ->seconds(false)
                                    ->required(fn(Get $get): bool => $get('status') == BillStatus::PAID->value)
                                    ->visible(fn(Get $get): bool => $get('status') == BillStatus::PAID->value)
                                    ->hidden(fn(Component $livewire): bool => $livewire instanceof CreateBill || $livewire instanceof ViewBill),
                                Select::make('payment_id')
                                    ->label(__('billing.payment_method'))
                                    ->options(fn() => Payment::whereIn('id', [PaymentMode::CASH->value, PaymentMode::CASH_DEPOSIT->value])
                                        ->pluck('payment_mode', 'id')
                                        ->map(fn($mode, $id) => PaymentMode::tryFrom($id)?->getLabel() ?? $mode))
                                    ->selectablePlaceholder(false)
                                    ->dehydrated(false)
                                    ->required(fn(Get $get): bool => $get('status') == BillStatus::PAID->value)
                                    ->visible(fn(Get $get): bool => $get('status') == BillStatus::PAID->value)
                                    ->hidden(fn(Component $livewire): bool => $livewire instanceof CreateBill || $livewire instanceof ViewBill),
                                SpatieMediaLibraryFileUpload::make('receipt')
                                    ->label(__('billing.receipt'))
                                    ->disk('cos')
                                    ->image()
                                    ->customProperties(['type' => 'bill-reminder-slip'])
                                    ->openable(true)
                                    ->required(fn(Get $get) => $get('status') == BillStatus::PAID->value)
                                    ->hidden(fn(Get $get) => $get('status') == BillStatus::UNPAID->value || $get('status') == BillStatus::CANCEL->value)
                                    ->visible(fn(Component $livewire): bool => $livewire instanceof EditBill),
                                Select::make('payer_name')
                                    ->label(__('billing.payer_name'))
                                    ->options(function ($record) {
                                        $user_id = $record->payers->pluck('payer_id');
                                        $users = [];
                                        $users = User::whereIn('id', $user_id)->pluck('name', 'id')->toArray();
                                        $users += ['Other' => 'Other'];

                                        return $users;
                                    })
                                    ->required()
                                    ->reactive()
                                    ->hidden(fn(Get $get) => $get('status') == BillStatus::UNPAID->value || $get('status') == BillStatus::CANCEL->value)
                                    ->visible(fn(Component $livewire): bool => $livewire instanceof EditBill),
                                TextInput::make('payer_name_other')
                                    ->label(__('billing.payer_name_for_other_payer_option'))
                                    ->required()
                                    ->reactive()
                                    ->hidden(fn(Get $get) => $get('payer_name') == null || $get('payer_name') != 'Other')
                                    ->maxLength(255),
                            ]),

                        Fieldset::make(__('billing.bill_items'))
                            ->columnSpanFull()
                            ->columns(1)
                            ->schema([
                                Repeater::make('items')
                                    ->label('')
                                    ->relationship()
                                    ->schema([
                                        TextInput::make('name')
                                            ->label(__('billing.expenses_type'))
                                            ->required()
                                            ->maxLength(255),
                                        TextInput::make('price')
                                            ->label(__('billing.amount_thb'))
                                            ->prefix('฿')
                                            ->stripCharacters(',')
                                            ->mask(RawJs::make('$money($input)'))
                                            ->required(),
                                        Radio::make('status')
                                            ->label(__('app.status'))
                                            ->options([
                                                BillStatus::UNPAID->value => BillStatus::UNPAID->getLabel(),
                                                BillStatus::CANCEL->value => BillStatus::CANCEL->getLabel(),
                                            ])
                                            ->columns(3)
                                            ->required()
                                            ->visible(fn(Component $livewire): bool => $livewire instanceof EditBill)
                                            ->disabled(fn(Get $get) => $get('status') == BillStatus::CANCEL->value),
                                        Radio::make('status')
                                            ->label(__('app.status'))
                                            ->options([
                                                BillStatus::PAID->value => BillStatus::PAID->getLabel(),
                                                BillStatus::UNPAID->value => BillStatus::UNPAID->getLabel(),
                                                BillStatus::CANCEL->value => BillStatus::CANCEL->getLabel(),
                                            ])
                                            ->columns(3)
                                            ->required()
                                            ->visible(fn(Component $livewire): bool => $livewire instanceof ViewBill),
                                    ])
                                    ->addActionLabel(__('billing.add_more_expenses'))
                                    ->columns(3)
                                    ->minItems(1),
                                Select::make('notification')
                                    ->label(__('app.notification'))
                                    ->options([
                                        'All' => (__('app.all')),
                                        'Main Owner' => (__('user.main_owner')),
                                        'Main Tenant' => (__('user.main_tenant')),
                                    ])
                                    ->searchable()
                                    ->required()
                                    ->rules([
                                        function (Get $get) {
                                            return function (string $attribute, $value, Closure $fail) use ($get) {
                                                if ($value == 'Main Owner') {
                                                    $check_unit_user = UnitUser::where('unit_id', $get('payer_unit_id'))->where('is_main_owner', 1)->first();

                                                    if (! $check_unit_user) {
                                                        $fail('You need to add Resident Main Owner for this unit before proceed!');
                                                    }
                                                } elseif ($value == 'Main Tenant') {
                                                    $check_unit_user = UnitUser::where('unit_id', $get('payer_unit_id'))->where('is_main_tenant', 1)->first();

                                                    if (! $check_unit_user) {
                                                        $fail('You need to add Resident Main Tenant for this unit before proceed!');
                                                    }
                                                }
                                            };
                                        },
                                    ])
                                    ->visible(fn(Component $livewire): bool => $livewire instanceof CreateBill),
                                Textarea::make('remark')
                                    ->label(__('app.remark')),
                            ]),
                    ])
            ]);
    }
}
