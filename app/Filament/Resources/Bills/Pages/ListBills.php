<?php

namespace App\Filament\Resources\Bills\Pages;

use App\Enums\Bill\BillStatus;
use App\Exports\TemplateBillReminder;
use App\Filament\Resources\Bills\BillPayeeImport;
use App\Filament\Resources\Bills\BillResource;
use App\Jobs\SendEmailBillInvoice;
use App\Models\BillPayeeSetting;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\Payer;
use App\Models\Unit;
use App\Models\UnitUser;
use App\Notifications\BillInvoiceCreated;
use Carbon\Carbon;
use Closure;
use Excel;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification as FilamentsNotification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Grid;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Livewire\Component;
use Throwable;

class ListBills extends ListRecords
{
    protected static string $resource = BillResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('UploadData')
                ->label(__('app.upload_data'))
                ->action(Closure::fromCallable([$this, 'openImportExcel']))
                ->schema([
                    Grid::make(['sm' => 2, 'xl' => 2, '2xl' => 2])
                        ->schema([
                            DatePicker::make('bill_date')
                                ->label(__('billing.bill_date'))
                                ->required()
                                ->displayFormat('Y-m-d'),
                            DatePicker::make('due_date')
                                ->label(__('billing.due_date'))
                                ->required()
                                ->displayFormat('Y-m-d'),
                        ]),
                    FileUpload::make('upload')
                        ->label(__('app.upload'))
                        ->preserveFilenames()
                        ->disk('local')
                        ->directory('uploads')
                        ->storeFileNamesIn('uploads'),
                ]),
            Action::make('downloadTemplate')
                ->label(__('app.download_template'))
                ->action(Closure::fromCallable([$this, 'downloadFileTemplate']))
                ->schema([
                    Select::make('residence_id')
                        ->label(__('app.mooban_or_residence'))
                        ->options(fn (Component $livewire) => $livewire instanceof ListBills
                                ? list_create_residences()
                                : list_residences()
                        )
                        ->default(function () {
                            $user = auth()->user();
                            if ($user->hasRole('Property Management')) {
                                return $user->propertyManagement?->id;
                            }

                            return null;
                        })
                        ->searchable()
                        ->required(),
                ]),
            CreateAction::make(),
        ];
    }

    public function openImportExcel($data)
    {
        try {
            $billDate = $data['bill_date'];
            $dueDate = $data['due_date'];
            $fileName = $data['upload'];
            $filePath = storage_path('app/'.$fileName);

            $import = new BillPayeeImport;
            Excel::import($import, $filePath);
            $rows = $import->getArray();
            $startRow = 2;

            $collections = [];

            // Prepare unit lookup
            $unitPairs = collect($rows)->skip($startRow)->map(fn ($row) => [
                'home_id' => $row['home_id'],
                'unit_number' => $row['house_unit'],
            ])->unique(fn ($item) => $item['home_id'].'_'.$item['unit_number'])->values();

            $units = Unit::with(['unitUsers', 'owners', 'tenants'])
                ->where(function ($query) use ($unitPairs) {
                    foreach ($unitPairs as $pair) {
                        $query->orWhere(fn ($q) => $q->where('home_id', $pair['home_id'])->where('unit_number', $pair['unit_number']));
                    }
                })->get()->keyBy(fn ($u) => $u->home_id.'_'.$u->unit_number);

            // Bill payee settings
            $residenceIds = $units->pluck('residence_id')->unique();
            $settings = BillPayeeSetting::whereIn('residence_id', $residenceIds)->get()->keyBy('residence_id');

            foreach (array_slice($rows, $startRow) as $index => $row) {
                $line = $index + $startRow;

                // Skip empty rows
                if (empty($row['home_id']) && empty($row['house_unit']) && empty($row['expenses_type']) && empty($row['amount']) && empty($row['notification'])) {
                    continue;
                }

                // Backfill if same home_id
                if ($index > 0 && $rows[$index]['home_id'] === $rows[$index - 1]['home_id']) {
                    foreach (['house_unit', 'bill_reminder_no', 'notification', 'remark'] as $field) {
                        if (empty($row[$field])) {
                            $row[$field] = $rows[$index - 1][$field];
                        }
                    }
                }

                // Validation
                foreach (['home_id', 'house_unit', 'expenses_type', 'amount', 'notification'] as $field) {
                    if (empty($row[$field])) {
                        return FilamentsNotification::make()->title("Missing Required Field: $field")
                            ->body(__('billing.error_in_row', ['row' => $line]))->warning()->persistent()->send();
                    }
                }

                if (! in_array($row['notification'], ['All', 'Main Owner', 'Main Tenant'])) {
                    return FilamentsNotification::make()->title(__('billing.invalid_notification'))
                        ->body(__('billing.error_in_row', ['row' => $line]))->warning()->persistent()->send();
                }

                $unitKey = $row['home_id'].'_'.$row['house_unit'];
                $unit = $units->get($unitKey);
                if (! $unit) {
                    return FilamentsNotification::make()->title(__('billing.home_id_unit_mismatch'))
                        ->body(__('billing.error_in_row', ['row' => $line]))->warning()->persistent()->send();
                }

                $setting = $settings->get($unit->residence_id);
                if (! $setting) {
                    return FilamentsNotification::make()->title(__('billing.missing_bill_payee_setting'))
                        ->body(__('billing.error_in_row', ['row' => $line]))->warning()->persistent()->send();
                }

                $unitUsers = match ($row['notification']) {
                    'All' => $unit->unitUsers,
                    'Main Owner' => $unit->owners->where('is_main_owner', 1),
                    'Main Tenant' => $unit->tenants->where('is_main_tenant', 1),
                    default => collect(),
                };

                $collections[$row['home_id']][] = [
                    'row' => $line,
                    'home_id' => $row['home_id'],
                    'house_unit' => $row['house_unit'],
                    'bill_reminder_no' => $row['bill_reminder_no'],
                    'expenses_type' => $row['expenses_type'],
                    'amount' => (float) $row['amount'],
                    'bill_date' => Carbon::parse($billDate)->format('Y-m-d'),
                    'due_date' => Carbon::parse($dueDate)->format('Y-m-d'),
                    'notification' => $row['notification'],
                    'remark' => $row['remark'],
                    'residence_id' => $unit->residence_id,
                    'unit_id' => $unit->id,
                    'bill_payee_setting_id' => $setting->id,
                    'unit_users' => $unitUsers,
                ];
            }

            DB::beginTransaction();

            foreach ($collections as $group) {
                $totalAmount = collect($group)->sum('amount');
                $billDate = $group[0]['bill_date'];
                $month = date('m', strtotime($billDate));
                $year = date('Y', strtotime($billDate));

                $invoiceCount = Invoice::whereYear('bill_date', $year)->whereMonth('bill_date', $month)->count();
                $invoiceNo = 'INV'.$year.$month.str_pad($invoiceCount + 1, 4, '0', STR_PAD_LEFT);

                $invoice = $this->storeInvoice([
                    'invoice_no' => $invoiceNo,
                    'bill_payee_setting_id' => $group[0]['bill_payee_setting_id'],
                    'payer_unit_id' => $group[0]['unit_id'],
                    'bill_no' => $group[0]['bill_reminder_no'],
                    'bill_date' => $group[0]['bill_date'],
                    'due_date' => $group[0]['due_date'],
                    'status' => BillStatus::UNPAID->value,
                    'total_amount' => $totalAmount,
                    'amount_due' => $totalAmount,
                    'remark' => $group[0]['remark'],
                ]);

                if (! $invoice) {
                    return FilamentsNotification::make()->title(__('billing.failed_to_save'))->body(__('billing.error_in_row', ['row' => $group[0]['row']]))->danger()->persistent()->send();
                }

                foreach ($group as $item) {
                    $item['invoice_id'] = $invoice->id;
                    $this->storeInvoiceItem($item);
                }

                $payers = collect();
                foreach ($group[0]['unit_users'] as $unitUser) {
                    $payers->push($this->storeInvoicePayer($invoice, $unitUser));
                }

                DB::commit();

                // Send notifications after commit
                $this->sendNotifications($invoice, $payers);

                \Log::info($invoice->id);

                // Dispatch queued email using ID
                SendEmailBillInvoice::dispatch($invoice->id)->afterCommit();
            }

            return redirect('/admin/bill-reminders');
        } catch (Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    public function downloadFileTemplate($data)
    {
        try {
            $residenceID = $data['residence_id'];
            $unitData = Unit::where('residence_id', $residenceID)->whereNull('deleted_at')->get();

            return Excel::download(new TemplateBillReminder($unitData), 'MyMooBan Bill Reminder Import Template.xlsx');
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    private function storeInvoice(array $data): Invoice
    {
        $invoice = Invoice::create($data);

        if ((float) $invoice->amount_due === 0.0) {
            $invoice->items()->update(['status' => BillStatus::PAID->value]);
            $invoice->update(['status' => BillStatus::PAID->value]);
        }

        return $invoice;
    }

    private function storeInvoiceItem(array $data)
    {
        Item::disableModelHistory();

        $item = Item::create([
            'invoice_id' => $data['invoice_id'],
            'name' => $data['expenses_type'],
            'price' => $data['amount'],
            'vat' => $data['amount'],
            'status' => BillStatus::UNPAID->value,
        ]);

        Item::enableModelHistory();

        return $item;
    }

    private function storeInvoicePayer(Invoice $invoice, UnitUser $unitUser): Payer
    {
        return Payer::create([
            'invoice_id' => $invoice->id,
            'payer_id' => $unitUser->user_id,
        ]);
    }

    private function sendNotifications(Invoice $invoice, $payers): void
    {
        $users = $payers->pluck('user')->filter();

        if ($users->isEmpty()) {
            return;
        }

        Notification::send($users, new BillInvoiceCreated($invoice));
    }
}
