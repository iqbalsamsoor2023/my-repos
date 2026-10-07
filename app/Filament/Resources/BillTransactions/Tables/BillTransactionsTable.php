<?php

namespace App\Filament\Resources\BillTransactions\Tables;

use App\Actions\Audit\CreateAuditAction;
use App\Actions\BillReminderSlip\ExportPaymentEvidenceAction;
use App\Enums\Bill\PaymentMode;
use App\Enums\Bill\TransactionStatus;
use App\Exports\BillTransactionExport;
use App\Models\Payment;
use App\Models\Transaction;
use App\Services\FilamentExport\FilamentExportBulkAction;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class BillTransactionsTable
{
    public static function configure(Table $table): Table
    {
        $user = auth()->user();

        return $table
            ->columns([
                TextColumn::make('ref_no')
                    ->label(__('billing.receipt_no'))
                    ->toggleable(),
                TextColumn::make('invoice.invoice_no')
                    ->label(__('billing.invoice_no'))
                    ->toggleable(),
                TextColumn::make('invoice.bill_no')
                    ->label(__('billing.bill_no'))
                    ->toggleable(),
                TextColumn::make('invoice.unit.residence.name')
                    ->label(__('app.mooban_or_residence'))
                    ->toggleable()
                    ->description(fn (Transaction $record): string => $record?->invoice?->unit?->residence?->name_th ?? '-'),
                TextColumn::make('invoice.unit.unit_number')
                    ->label(__('unit.unit_number'))
                    ->toggleable(),
                TextColumn::make('invoice.unit.home_id')
                    ->label(__('app.home_id'))
                    ->toggleable(),
                TextColumn::make('paymentMethod.payment_mode')
                    ->label(__('billing.payment_method'))
                    ->formatStateUsing(fn (string $state, Transaction $record): string => PaymentMode::tryFrom($record->payment_id)?->getLabel() ?? $state)
                    ->toggleable(),
                TextColumn::make('payer_name')
                    ->label(__('billing.payer_name'))
                    ->toggleable(),
                TextColumn::make('paid_amount')
                    ->label(__('billing.amount_paid_thb'))
                    ->toggleable(),
                TextColumn::make('transaction_datetime')
                    ->label(__('billing.payment_date'))
                    ->getStateUsing(function (Transaction $record) {
                        return date('d-M-y', strtotime($record->transaction_datetime));
                    })
                    ->toggleable(),
                TextColumn::make('transaction_time')
                    ->label(__('billing.payment_time'))
                    ->getStateUsing(function (Transaction $record) {
                        return date('H:i:s', strtotime($record->transaction_datetime));
                    })
                    ->toggleable(),
                TextColumn::make('invoice.due_date')
                    ->label(__('billing.due_date'))
                    ->toggleable(),
                TextColumn::make('status')
                    ->label(__('app.status'))
                    ->badge()
                    ->formatStateUsing(function (string $state): string {
                        $statusMapping = [
                            TransactionStatus::PENDING->value => __('app.'.strtolower(TransactionStatus::PENDING->name)),
                            TransactionStatus::ACCEPTED->value => __('billing.'.strtolower(TransactionStatus::ACCEPTED->name)),
                            TransactionStatus::REJECTED->value => __('billing.'.strtolower(TransactionStatus::REJECTED->name)),
                        ];

                        return $statusMapping[$state];
                    })
                    ->colors([
                        'warning' => fn ($state): bool => $state === 1,
                        'success' => fn ($state): bool => $state === 2,
                        'danger' => fn ($state): bool => $state === 3,
                    ])
                    ->toggleable(),
                TextColumn::make('reviewedBy.name')
                    ->label(__('user.checked_by'))
                    ->toggleable(),
                TextColumn::make('remark')
                    ->label(__('app.remark'))
                    ->limit(30)
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('app.created_at_date'))
                    ->getStateUsing(function (Transaction $record) {
                        return $record->created_at->format('d-M-y');
                    })
                    ->toggleable(),
                TextColumn::make('created_at_time')
                    ->label(__('app.created_at_time'))
                    ->getStateUsing(function (Transaction $record) {
                        return $record->created_at->format('H:i:s');
                    })
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->label(__('billing.checked_at_datetime'))
                    ->getStateUsing(function (Transaction $record) {
                        return $record->status != TransactionStatus::PENDING->value ? $record->updated_at->format('d-M-y H:i:s') : '-';
                    })
                    ->toggleable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('residence')
                    ->label(__('app.mooban_or_residence'))
                    ->options(list_residences())
                    ->searchable()
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['value'],
                                fn (Builder $query): Builder => $query->whereHas('invoice.unit.residence', function ($q) use ($data) {
                                    return $q->whereId($data['value']);
                                }),
                            );
                    })
                    ->visible($user->hasRole(['Super Admin', 'Property Management Operation Center'])),
                Filter::make('unit_number')
                    ->schema([
                        TextInput::make('unit_number')
                            ->label(__('unit.unit_number')),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (isset($data['unit_number'])) {
                            return $query->whereHas('invoice.unit', function ($q) use ($data) {
                                return $q->where('unit_number', 'LIKE', '%'.$data['unit_number'].'%');
                            });
                        }
                    }),
                Filter::make('payer_name')
                    ->schema([
                        TextInput::make('payer_name')
                            ->label(__('billing.payer_name')),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (isset($data['payer_name'])) {
                            return $query->where('payer_name', 'LIKE', '%'.$data['payer_name'].'%');
                        }
                    }),
                Filter::make('receipt_no')
                    ->schema([
                        TextInput::make('receipt_no')
                            ->label(__('billing.receipt_no')),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (isset($data['receipt_no'])) {
                            return $query->where('ref_no', $data['receipt_no']);
                        }
                    }),
                Filter::make('invoice_no')
                    ->schema([
                        TextInput::make('invoice_no')
                            ->label(__('billing.invoice_no')),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (isset($data['invoice_no'])) {
                            return $query->whereHas('invoice', function ($q) use ($data) {
                                return $q->where('invoice_no', $data['invoice_no']);
                            });
                        }
                    }),
                SelectFilter::make('payment_id')
                    ->label(__('billing.payment_method'))
                    ->options(Payment::all()->mapWithKeys(fn (Payment $payment) => [
                        $payment->id => PaymentMode::tryFrom($payment->id)?->getLabel() ?? $payment->payment_details,
                    ])),
                SelectFilter::make('status')
                    ->label(__('app.status'))
                    ->options([
                        TransactionStatus::PENDING->value => __('app.'.strtolower(TransactionStatus::PENDING->name)),
                        TransactionStatus::ACCEPTED->value => __('billing.'.strtolower(TransactionStatus::ACCEPTED->name)),
                        TransactionStatus::REJECTED->value => __('billing.'.strtolower(TransactionStatus::REJECTED->name)),
                    ]),
                Filter::make('payment_date')
                    ->label(__('billing.payment_date'))
                    ->schema([
                        DatePicker::make('payment_date_from')
                            ->label(__('billing.payment_date_from')),
                        DatePicker::make('payment_date_until')
                            ->label(__('billing.payment_date_until')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['payment_date_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('transaction_datetime', '>=', $date),
                            )
                            ->when(
                                $data['payment_date_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('transaction_datetime', '<=', $date),
                            );
                    }),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(3)
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->visible(fn (Transaction $record): string => $record->status == 1),
                    ViewAction::make(),
                    Action::make('receipt')
                        ->label(__('billing.receipt'))
                        ->icon('heroicon-o-document')
                        ->url(fn (Transaction $record): string => route('bill-reminder-slip.receipt', $record->id))
                        ->openUrlInNewTab()
                        ->visible(fn (Transaction $record): string => $record->status == 2),
                    Action::make('exportReceipt')
                        ->label(__('billing.export_receipt'))
                        ->icon('heroicon-o-arrow-down-tray')
                        ->url(fn (Transaction $record): string => route('bill-reminder-slip.export', $record->id))
                        ->openUrlInNewTab()
                        ->visible(fn (Transaction $record): bool => $record->status == 2),
                ]),
            ], position: RecordActionsPosition::BeforeColumns)
            ->toolbarActions([
                FilamentExportBulkAction::make('export')
                    ->fileName('Bill-Slip-Report')
                    ->disableAdditionalColumns()
                    ->disableCsv()
                    ->disablePdf()
                    ->disableFilterColumns()
                    // The package builds its preview from the table's visible columns,
                    // so it showed MooBan/Residence and Home ID, which this report does
                    // not export. Hidden so the modal cannot contradict the file.
                    ->disablePreview()
                    ->action(function (Component $livewire) {
                        // bulk action form data
                        $fileName = ($data['file_name'] ?? 'Bill-Slip-Report').'.xlsx';

                        // Selected records
                        $transactions = $livewire->getSelectedTableRecords();

                        $transactions = Transaction::whereIn('id', $transactions->pluck('id'))
                            ->latest('id')
                            ->get();

                        $audit = new CreateAuditAction;
                        $audit->execute(new Request([
                            'user_type' => get_class(auth()->user()),
                            'user_id' => auth()->id(),
                            'event' => 'exported',
                            'old_values' => [],
                            'new_values' => ['action' => 'exported bill reminder records'],
                            'url' => request()->fullUrl(),
                            'ip_address' => request()->ip(),
                            'user_agent' => request()->userAgent(),
                        ]));

                        return Excel::download(new BillTransactionExport($transactions), $fileName);
                    }),
                BulkAction::make('exportPdf')
                    ->label(__('billing.export_pdf'))
                    ->icon('heroicon-o-document-arrow-down')
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records) {
                        $transactions = Transaction::with([
                            'invoice.unit:id,unit_number',
                            'invoice.billPayeeSetting:id,payee_name,payee_name_th',
                            'invoice.items:id,invoice_id,name,status',
                            'media',
                        ])
                            ->whereIn('id', $records->pluck('id'))
                            ->latest('id')
                            ->get();

                        $audit = new CreateAuditAction;
                        $audit->execute(new Request([
                            'user_type' => get_class(auth()->user()),
                            'user_id' => auth()->id(),
                            'event' => 'exported',
                            'old_values' => [],
                            'new_values' => ['action' => 'exported bill reminder payment evidence'],
                            'url' => request()->fullUrl(),
                            'ip_address' => request()->ip(),
                            'user_agent' => request()->userAgent(),
                        ]));

                        return (new ExportPaymentEvidenceAction)->execute($transactions);
                    }),
                DeleteBulkAction::make()
                    ->hidden(auth()->user()->hasRole(['Admin'])),
            ]);
    }
}
