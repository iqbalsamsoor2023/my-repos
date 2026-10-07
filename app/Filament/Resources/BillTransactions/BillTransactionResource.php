<?php

namespace App\Filament\Resources\BillTransactions;

use App\Filament\Resources\BillTransactions\Pages\EditBillTransaction;
use App\Filament\Resources\BillTransactions\Pages\ListBillTransactions;
use App\Filament\Resources\BillTransactions\Pages\ViewBillTransaction;
use App\Filament\Resources\BillTransactions\Schemas\BillTransactionForm;
use App\Filament\Resources\BillTransactions\Tables\BillTransactionsTable;
use App\Models\Transaction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BillTransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'bill-reminder-slips';

    public static function getNavigationGroup(): string
    {
        return __('menu.accounting_management');
    }

    public static function getModelLabel(): string
    {
        return __('billing.bill_reminder_slip');
    }

    public static function getPluralModelLabel(): string
    {
        return __('menu.bill_reminder_slips');
    }

    public static function getNavigationLabel(): string
    {
        return __('menu.accountingManagement.manage_bill_slips');
    }

    public static function form(Schema $schema): Schema
    {
        return BillTransactionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BillTransactionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBillTransactions::route('/'),
            'view' => ViewBillTransaction::route('/{record}'),
            'edit' => EditBillTransaction::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $query = Transaction::with([
            'invoice:id,invoice_no,bill_no,payer_unit_id,due_date',
            'invoice.unit:id,residence_id,unit_number,home_id',
            'invoice.unit.residence:id,name,name_th,property_management_id',
            'paymentMethod:id,payment_mode',
            'reviewedBy:id,name',
        ]);

        if ($user->hasRole('Property Management')) {
            $query->whereHas('invoice.unit.residence', fn ($q) => $q->where('property_management_user_id', $user->id));
        } elseif ($user->hasRole('Property Management Operation Center')) {
            $residence_ids = get_residence_by_property_management_operation_center($user->id);

            $query->whereHas('invoice.unit', function ($subQuery) use ($residence_ids) {
                $subQuery->whereIn('residence_id', $residence_ids);
            });
        }

        return $query;
    }
}
