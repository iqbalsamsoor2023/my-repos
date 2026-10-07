<?php

namespace App\Filament\Resources\PrivateMaintenances;

use App\Filament\Resources\Maintenances\MaintenanceResource;
use App\Filament\Resources\PrivateMaintenances\Pages\CreatePrivateMaintenance;
use App\Filament\Resources\PrivateMaintenances\Pages\EditPrivateMaintenance;
use App\Filament\Resources\PrivateMaintenances\Pages\ListPrivateMaintenances;
use App\Filament\Resources\PrivateMaintenances\Pages\ViewPrivateMaintenance;
use App\Filament\Resources\PrivateMaintenances\RelationManagers\MaintenanceProgressionsRelationManager;
use App\Models\Maintenance;
use App\Models\Unit;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PrivateMaintenanceResource extends Resource
{
    protected static ?string $model = Maintenance::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCpuChip;

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'private-maintenances';

    public static function getNavigationGroup(): string
    {
        return __('menu.maintenance_management');
    }

    public static function getModelLabel(): string
    {
        return __('Private Maintenance');
    }

    public static function getPluralModelLabel(): string
    {
        return __('menu.private_maintenances');
    }

    public static function form(Schema $schema): Schema
    {
        return MaintenanceResource::form($schema);
    }

    public static function table(Table $table): Table
    {
        $privateMaintenances = new ListPrivateMaintenances;

        return MaintenanceResource::table($table)
            ->filters($privateMaintenances->getTableFilters())
            ->filtersLayout(FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(3);
    }

    public static function getRelations(): array
    {
        return [
            MaintenanceProgressionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPrivateMaintenances::route('/'),
            'create' => CreatePrivateMaintenance::route('/create'),
            'edit' => EditPrivateMaintenance::route('/{record}/edit'),
            'view' => ViewPrivateMaintenance::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        $query = parent::getEloquentQuery()
            ->where('maintainable_type', Unit::class)
            ->select([
                'id',
                'maintainable_claim_number',
                'maintainable_type',
                'maintainable_id',
                'issue_description',
                'status',
                'is_verified',
                'completed_remark',
                'verification_description',
                'appointment_datetime',
                'rating',
                'claimable_item_details',
                'reported_by',
                'created_at',
                'private_claim_category_id',
                'private_claim_item_id',
                'private_claim_item_title_id',
                'other_private_claim_item',
                'updated_at']) // only necessary columns
            ->with([
                'maintainable' => function ($q) {
                    $q->select(['id', 'residence_id', 'move_in_at', 'unit_number']);
                },
                'maintainable.residence' => function ($q) {
                    $q->select(['id', 'name', 'name_th']);
                },
                'reportedBy:id,name,phone_no',
                'privateClaimCategory',
                'privateClaimItem',
                'privateClaimItemTitle',
            ]);

        if ($user->hasRole('Property Management')) {
            $query->whereHasMorph(
                'maintainable',
                [Unit::class],
                fn ($q) => $q->whereHas('residence', fn ($r) => $r->where('property_management_user_id', $user->id))
            );
        } elseif ($user->hasRole('Property Management Operation Center')) {
            $residence_ids = get_residence_by_property_management_operation_center($user->id);

            $query->whereHasMorph('maintainable', [Unit::class], function ($subQuery) use ($residence_ids) {
                $subQuery->whereIn('residence_id', $residence_ids);
            });
        }

        return $query;
    }
}
