<?php

namespace App\Filament\Resources\BillTransactions\Schemas;

use App\Enums\Bill\PaymentMode;
use App\Enums\Bill\TransactionStatus;
use App\Forms\Components\Bill\BillItem;
use App\Models\Bank;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Residence;
use App\Models\Unit;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class BillTransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        $user = auth()->user();

        return $schema
            ->components([
                Section::make(__('menu.bill_reminder_slips'))
                    ->description(__('billing.bill_reminder_slip_details'))
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Fieldset::make(__('user.resident_details'))
                            ->columnSpanFull()
                            ->schema([
                                Select::make('residence')
                                    ->label(__('app.mooban_or_residence'))
                                    ->options(function ($record) {
                                        return Residence::whereId($record->invoice->unit->residence_id)->pluck('name');
                                    })
                                    ->selectablePlaceholder(false)
                                    ->dehydrated(false),
                                Select::make('unit')
                                    ->label(__('unit.unit_number'))
                                    ->options(function ($record) {
                                        return Unit::whereId($record->invoice->payer_unit_id)->pluck('unit_number');
                                    })
                                    ->selectablePlaceholder(false)
                                    ->dehydrated(false),
                                Select::make('home_id')
                                    ->label(__('app.home_id'))
                                    ->options(function ($record) {
                                        return Unit::whereId($record->invoice->payer_unit_id)->pluck('home_id');
                                    })
                                    ->selectablePlaceholder(false)
                                    ->dehydrated(false),
                            ]),

                        Fieldset::make(__('billing.bill_details'))
                            ->columnSpanFull()
                            ->schema([
                                Select::make('invoice_no')
                                    ->label(__('billing.invoice_no'))
                                    ->options(function ($record) {
                                        return Invoice::whereId($record->invoice_id)->pluck('invoice_no');
                                    })
                                    ->selectablePlaceholder(false)
                                    ->dehydrated(false),
                                Select::make('due_date')
                                    ->label(__('billing.due_date'))
                                    ->options(function ($record) {
                                        return Invoice::whereId($record->invoice_id)->pluck('due_date');
                                    })
                                    ->selectablePlaceholder(false)
                                    ->dehydrated(false),
                                BillItem::make('bill_lists')
                                    ->label(__('billing.bill_lists'))
                                    ->dehydrated(false),
                            ]),

                        Fieldset::make(__('billing.payment_details'))
                            ->columnSpanFull()
                            ->schema([
                                SpatieMediaLibraryFileUpload::make('receipt')
                                    ->customProperties(['type' => 'bill-reminder-slip'])
                                    ->label(__('billing.receipt'))
                                    ->disk('cos')
                                    ->image()
                                    ->openable(true),
                                Select::make('payment_method')
                                    ->label(__('billing.payment_method'))
                                    ->relationship('paymentMethod', 'payment_mode')
                                    ->getOptionLabelFromRecordUsing(fn (Payment $record) => PaymentMode::tryFrom($record->id)?->getLabel() ?? $record->payment_mode)
                                    ->disabled()
                                    ->dehydrated(false),
                                Select::make('bank')
                                    ->label(__('billing.bank'))
                                    ->options(function ($record) {
                                        return Bank::whereId($record->bankAccountDetail->bank_id)->pluck('name');
                                    })
                                    ->default(function ($record) {
                                        return Bank::whereId($record->bankAccountDetail->bank_id)->pluck('name');
                                    })
                                    ->selectablePlaceholder(false)
                                    ->dehydrated(false)
                                    ->hidden(function ($record) {
                                        if (is_null($record->bill_payee_bank_detail_id) == true) {
                                            return true;
                                        }
                                    }),
                                Select::make('account_name')
                                    ->label(__('billing.account_name'))
                                    ->relationship('bankAccountDetail', 'payee_account_name')
                                    ->disabled()
                                    ->dehydrated(false),
                                Select::make('account_number')
                                    ->label(__('billing.account_number'))
                                    ->relationship('bankAccountDetail', 'payee_account_number')
                                    ->disabled()
                                    ->dehydrated(false),
                                TextInput::make('payer_name')
                                    ->label(__('billing.payer_name'))
                                    ->disabled()
                                    ->dehydrated(false),
                                TextInput::make('paid_amount')
                                    ->label(__('billing.paid_amount'))
                                    ->prefix('฿')
                                    ->disabled()
                                    ->dehydrated(false),
                                DatePicker::make('transaction_datetime')
                                    ->label(__('billing.payment_date')),
                                TimePicker::make('transaction_time')
                                    ->label(__('billing.payment_time'))
                                    ->native(false)
                                    ->seconds(false),
                                Radio::make('status')
                                    ->label(__('app.status'))
                                    ->options([
                                        TransactionStatus::PENDING->value => __('app.'.strtolower(TransactionStatus::PENDING->name)),
                                        TransactionStatus::ACCEPTED->value => __('billing.'.strtolower(TransactionStatus::ACCEPTED->name)),
                                        TransactionStatus::REJECTED->value => __('billing.'.strtolower(TransactionStatus::REJECTED->name)),
                                    ])
                                    ->columns(5)
                                    ->required(),
                                Textarea::make('remark')
                                    ->label(__('app.remark'))
                                    ->required(fn (Get $get) => $get('status') == TransactionStatus::REJECTED->value),
                            ]),
                    ]),
            ]);
    }
}
