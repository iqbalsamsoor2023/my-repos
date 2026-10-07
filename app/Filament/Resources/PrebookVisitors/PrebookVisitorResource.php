<?php

namespace App\Filament\Resources\PrebookVisitors;

use App\Filament\Resources\PrebookVisitors\Pages\CreatePrebookVisitor;
use App\Filament\Resources\PrebookVisitors\Pages\EditPrebookVisitor;
use App\Filament\Resources\PrebookVisitors\Pages\ListPrebookVisitors;
use App\Filament\Resources\PrebookVisitors\Pages\QrPrebookVisitor;
use App\Filament\Resources\PrebookVisitors\Pages\ViewPrebookVisitor;
use App\Filament\Resources\PrebookVisitors\Schemas\PrebookVisitorForm;
use App\Filament\Resources\PrebookVisitors\Schemas\PrebookVisitorInfolist;
use App\Filament\Resources\PrebookVisitors\Tables\PrebookVisitorsTable;
use App\Models\PreregisterVisitor;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PrebookVisitorResource extends Resource
{
    protected static ?string $model = PreregisterVisitor::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDeviceTablet;

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'prebook-helpdesks';

    public static function getNavigationGroup(): string
    {
        return __('menu.security_management');
    }

    public static function getModelLabel(): string
    {
        return __('visitor.prebook_helpdesk');
    }

    public static function getPluralModelLabel(): string
    {
        return __('visitor.prebook_helpdesks');
    }

    public static function getNavigationLabel(): string
    {
        return __('menu.securityManagement.manage_prebook_helpdesks');
    }

    public static function form(Schema $schema): Schema
    {
        return PrebookVisitorForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PrebookVisitorInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PrebookVisitorsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPrebookVisitors::route('/'),
            'create' => CreatePrebookVisitor::route('/create'),
            'view' => ViewPrebookVisitor::route('/{record}'),
            'edit' => EditPrebookVisitor::route('/{record}/edit'),
            'qr' => QrPrebookVisitor::route('/qr/{preregisterVisitorId}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        $query = parent::getEloquentQuery()
            ->with([
                'unit:id,unit_number,residence_id',
                'unit.residence:id,name,name_th',
                'visitor:id,name,contact_no,id_type,id_number',
            ]);

        if ($user->hasRole('Property Management')) {
            $query->whereHas('unit.residence', fn ($q) => $q->where('property_management_user_id', $user->id));
        } elseif ($user->hasRole('Property Management Operation Center')) {
            $residence_ids = get_residence_by_property_management_operation_center($user->id);

            $query->whereHas('unit', function ($subQuery) use ($residence_ids) {
                $subQuery->whereIn('residence_id', $residence_ids);
            });
        }

        return $query;
    }
}
