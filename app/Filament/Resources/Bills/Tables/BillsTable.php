<?php

namespace App\Filament\Resources\Bills\Tables;

use App\Actions\Audit\CreateAuditAction;
use App\Enums\Bill\BillStatus;
use App\Exports\BillExport;
use App\Models\Invoice;
use App\Models\UnitUser;
use App\Services\FilamentExport\FilamentExportBulkAction;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\ColumnGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class BillsTable
{
    public static function configure(Table $table): Table
    {
        $user = auth()->user();

        return $table
            ->columns([
                TextColumn::make('invoice_no')
                    ->label(__('billing.invoice_no'))
                    ->toggleable(),
                TextColumn::make('unit.residence.name')
                    ->label(__('app.mooban_or_residence'))
                    ->toggleable()
                    ->description(fn (Invoice $record): string => $record?->unit?->residence?->name_th ?? '-')
                    ->hidden($user->hasRole(['Property Management'])),
                TextColumn::make('unit.unit_number')
                    ->label(__('unit.unit_number'))
                    ->toggleable(),
                TextColumn::make('unit.home_id')
                    ->label(__('app.home_id'))
                    ->toggleable(),
                TextColumn::make('total_amount')
                    ->label(__('billing.amount_thb'))
                    ->toggleable(),
                TextColumn::make('amount_due')
                    ->label(__('billing.amount_due_thb'))
                    ->toggleable(),
                TextColumn::make('status')
                    ->label(__('app.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => BillStatus::tryFrom((int) $state)?->getLabel() ?? $state)
                    ->colors([
                        'danger' => fn ($state): bool => $state === BillStatus::UNPAID->value,
                        'success' => fn ($state): bool => $state === BillStatus::PAID->value,
                        'warning' => fn ($state): bool => $state === BillStatus::PENDING->value,
                        'primary' => fn ($state): bool => $state === BillStatus::CANCEL->value,
                    ])
                    ->toggleable(),
                TextColumn::make('bill_date')
                    ->label(__('billing.bill_date'))
                    ->toggleable(),
                TextColumn::make('due_date')
                    ->label(__('billing.due_date'))
                    ->toggleable(),
                TextColumn::make('remark')
                    ->label(__('app.remark'))
                    ->limit(10)
                    ->toggleable(),
                TextColumn::make('pay_by')
                    ->label(__('billing.pay_by'))
                    ->getStateUsing(function (Invoice $record) {
                        if (count($record->payers) > 1) {
                            return 'All';
                        } else {
                            foreach ($record->payers as $payer) {
                                $unitUser = UnitUser::where('user_id', $payer->payer_id)
                                    ->where('unit_id', $record->payer_unit_id)
                                    ->first();

                                if (is_null($unitUser) == false) {
                                    if ($unitUser->is_main_owner == true) {
                                        return __('user.main_owner');
                                    } elseif ($unitUser->is_main_tenant == true) {
                                        return __('user.main_tenant');
                                    } else {
                                        return 'All';
                                    }
                                }
                            }
                        }
                    })
                    ->toggleable(),
                TextColumn::make('payers.user.name')
                    ->label(__('billing.payer_name'))
                    ->getStateUsing(function (Invoice $record) {
                        $payers = [];

                        foreach ($record->payers as $payer) {
                            $payers[] = ucfirst($payer->user->name ?? null);
                        }

                        return $payers;
                    })
                    ->toggleable(),
                ColumnGroup::make(__('app.created_at'), [
                    TextColumn::make('created_at_date')
                        ->label(__('app.date'))
                        ->getStateUsing(function (Invoice $record) {
                            return $record->created_at->format('d-M-y');
                        }),
                    TextColumn::make('created_at_time')
                        ->label(__('app.time'))
                        ->getStateUsing(function (Invoice $record) {
                            return $record->created_at->format('H:i:s');
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('residence')
                    ->label(__('app.mooban_or_residence'))
                    ->options(list_residences())
                    ->searchable()
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['value'],
                                fn (Builder $query): Builder => $query->whereHas('unit.residence', function ($q) use ($data) {
                                    return $q->whereId($data['value']);
                                }),
                            );
                    })
                    ->visible($user->hasRole(['Super Admin', 'Admin', 'Property Management Operation Center'])),
                Filter::make('unit_number')
                    ->schema([
                        TextInput::make('unit_number')
                            ->label(__('unit.unit_number')),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (isset($data['unit_number'])) {
                            return $query->whereHas('unit', function ($q) use ($data) {
                                return $q->where('unit_number', 'LIKE', '%'.$data['unit_number'].'%');
                            });
                        }
                    }),
                Filter::make('invoice_no')
                    ->schema([
                        TextInput::make('invoice_no')
                            ->label(__('billing.invoice_no')),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (isset($data['invoice_no'])) {
                            return $query->where('invoice_no', $data['invoice_no']);
                        }
                    }),
                SelectFilter::make('status')
                    ->label(__('app.status'))
                    ->options([
                        BillStatus::UNPAID->value => BillStatus::UNPAID->getLabel(),
                        BillStatus::PAID->value => BillStatus::PAID->getLabel(),
                        BillStatus::PENDING->value => BillStatus::PENDING->getLabel(),
                        BillStatus::CANCEL->value => BillStatus::CANCEL->getLabel(),
                    ]),
                Filter::make('bill_date_range')
                    ->schema([
                        DatePicker::make('bill_date_from')
                            ->label(__('billing.bill_date_from')),
                        DatePicker::make('bill_date_until')
                            ->label(__('billing.bill_date_until')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['bill_date_from'],
                                fn (Builder $query, $date): Builder => $query->where('bill_date', '>=', $date),
                            )
                            ->when(
                                $data['bill_date_until'],
                                fn (Builder $query, $date): Builder => $query->where('bill_date', '<=', $date),
                            );
                    }),
                Filter::make('due_date_range')
                    ->schema([
                        DatePicker::make('due_date_from')
                            ->label(__('billing.due_date_from')),
                        DatePicker::make('due_date_until')
                            ->label(__('billing.due_date_until')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['due_date_from'],
                                fn (Builder $query, $date): Builder => $query->where('due_date', '>=', $date),
                            )
                            ->when(
                                $data['due_date_until'],
                                fn (Builder $query, $date): Builder => $query->where('due_date', '<=', $date),
                            );
                    }),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(3)
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->visible(fn (Invoice $record): bool => $record->status == BillStatus::UNPAID->value),
                    ViewAction::make(),
                    Action::make('invoice')
                        ->icon('heroicon-o-document')
                        ->url(fn (Invoice $record): string => route('bill-reminder.invoice', $record))
                        ->openUrlInNewTab(),
                ]),
            ], position: RecordActionsPosition::BeforeColumns)
            ->toolbarActions([
                FilamentExportBulkAction::make('export')
                    ->fileName('Bill-Report')
                    ->disableAdditionalColumns()
                    ->disableCsv()
                    ->disablePdf()
                    ->defaultPageOrientation('landscape')
                    ->disableFilterColumns()
                    // The package builds its preview from the table's visible columns,
                    // so it showed MooBan/Residence and Home ID, which this report does
                    // not export. Hidden so the modal cannot contradict the file.
                    ->disablePreview()
                    ->action(function (Component $livewire) {
                        // bulk action form data
                        $fileName = ($data['file_name'] ?? 'Bill-Report').'.xlsx';

                        // Selected records
                        $invoices = $livewire->getSelectedTableRecords();

                        $invoices = Invoice::whereIn('id', $invoices->pluck('id'))
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

                        return Excel::download(new BillExport($invoices), $fileName);
                    }),
            ]);
    }
}
