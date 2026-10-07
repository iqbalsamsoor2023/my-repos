<?php

namespace App\Filament\Resources\BillPayeeSettings;

use App\Filament\Resources\BillPayeeSettings\Pages\CreateBillPayeeSetting;
use App\Filament\Resources\BillPayeeSettings\Pages\EditBillPayeeSetting;
use App\Filament\Resources\BillPayeeSettings\Pages\ListBillPayeeSettings;
use App\Filament\Resources\BillPayeeSettings\Schemas\BillPayeeSettingForm;
use App\Filament\Resources\BillPayeeSettings\Tables\BillPayeeSettingsTable;
use App\Models\BillPayeeSetting;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BillPayeeSettingResource extends Resource
{
    protected static ?string $model = BillPayeeSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog;

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'bill-reminder-settings';

    protected static ?string $label = 'Bill Reminder Settings';

    public static function getNavigationGroup(): string
    {
        return __('menu.accounting_management');
    }

    public static function getModelLabel(): string
    {
        return __('billing.bill_reminder_setting');
    }

    public static function getPluralModelLabel(): string
    {
        return __('menu.bill_reminder_settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('menu.accountingManagement.manage_bill_settings');
    }

    public static function form(Schema $schema): Schema
    {
        return BillPayeeSettingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BillPayeeSettingsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBillPayeeSettings::route('/'),
            'create' => CreateBillPayeeSetting::route('/create'),
            'edit' => EditBillPayeeSetting::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $query = parent::getEloquentQuery()->with([
            'residence:id,name,name_th',
            'accounts.bank:id,name,name_th',
        ]);

        if ($user->hasRole('Property Management')) {
            $query->whereHas('residence', function ($q) use ($user) {
                $q->where('property_management_user_id', $user->id);
            });
        } elseif ($user->hasRole('Property Management Operation Center')) {
            $residence_ids = get_residence_by_property_management_operation_center($user->id);

            $query->whereIn('residence_id', $residence_ids);
        }

        return $query;
    }
}
