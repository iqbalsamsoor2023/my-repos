<?php

namespace App\Filament\Resources\Bills;

use App\Filament\Resources\Bills\Pages\CreateBill;
use App\Filament\Resources\Bills\Pages\EditBill;
use App\Filament\Resources\Bills\Pages\ListBills;
use App\Filament\Resources\Bills\Pages\ViewBill;
use App\Filament\Resources\Bills\RelationManagers\HistoriesRelationManager;
use App\Filament\Resources\Bills\Schemas\BillForm;
use App\Filament\Resources\Bills\Tables\BillsTable;
use App\Models\Invoice;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class BillResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'bill-reminders';

    public static function getNavigationGroup(): string
    {
        return __('menu.accounting_management');
    }

    public static function getModelLabel(): string
    {
        return __('billing.bill_reminder');
    }

    public static function getPluralModelLabel(): string
    {
        return __('menu.accountingManagement.manage_bill_reminders');
    }

    public static function getNavigationLabel(): string
    {
        return __('menu.accountingManagement.manage_bill_reminders');
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return BillForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BillsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            HistoriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBills::route('/'),
            'create' => CreateBill::route('/create'),
            'view' => ViewBill::route('/{record}'),
            'edit' => EditBill::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        $query = Invoice::with([
            'unit:id,residence_id,unit_number,home_id',
            'unit.residence:id,name,name_th,property_management_id',
            'unit.residence.propertyManagement:id,mmb_user_id',
            'unit.residence.propertyManagement.businessEntity:id,name',
            'payers:id,invoice_id,payer_id',
            'payers.user:id,name',
        ]);

        if ($user->hasRole('Property Management')) {
            $query->whereHas('unit.residence', fn ($q) => $q->where('property_management_user_id', $user->id));
        } elseif ($user->hasRole('Property Management Operation Center')) {
            $query->whereHas('unit.residence.propertyManagement', function ($q) use ($user) {
                $q->where('mmb_user_id', $user->id);
            });
        }

        return $query;
    }
}
