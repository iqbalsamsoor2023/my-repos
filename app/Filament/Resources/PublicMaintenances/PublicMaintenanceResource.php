<?php

namespace App\Filament\Resources\PublicMaintenances;

use App\Filament\Resources\Maintenances\MaintenanceResource;
use App\Filament\Resources\PublicMaintenances\Pages\CreatePublicMaintenance;
use App\Filament\Resources\PublicMaintenances\Pages\EditPublicMaintenance;
use App\Filament\Resources\PublicMaintenances\Pages\ListPublicMaintenances;
use App\Filament\Resources\PublicMaintenances\Pages\ViewPublicMaintenance;
use App\Filament\Resources\PublicMaintenances\RelationManagers\MaintenanceProgressionRelationManager;
use App\Models\Maintenance;
use App\Models\ResidenceAmenity;
use App\Models\ResidenceAmenityOption;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PublicMaintenanceResource extends Resource
{
    protected static ?string $model = Maintenance::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-pencil';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'public-maintenances';

    public static function getNavigationGroup(): string
    {
        return __('menu.maintenance_management');
    }

    public static function getModelLabel(): string
    {
        return __('Public Maintenance');
    }

    public static function getPluralModelLabel(): string
    {
        return __('menu.public_maintenances');
    }

    public static function form(Schema $schema): Schema
    {
        return MaintenanceResource::form($schema);
    }

    public static function table(Table $table): Table
    {
        $publicMaintenances = new ListPublicMaintenances;

        return MaintenanceResource::table($table)
            ->filters($publicMaintenances->getTableFilters())
            ->filtersLayout(FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(3);
    }

    public static function getRelations(): array
    {
        return [
            MaintenanceProgressionRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPublicMaintenances::route('/'),
            'create' => CreatePublicMaintenance::route('/create'),
            'edit' => EditPublicMaintenance::route('/{record}/edit'),
            'view' => ViewPublicMaintenance::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        $query = parent::getEloquentQuery()
            ->whereIn('maintainable_type', [ResidenceAmenity::class, ResidenceAmenityOption::class])
            ->with([
                'maintainable' => function ($morphQuery) {
                    if ($morphQuery->getModel() instanceof ResidenceAmenityOption) {
                        $morphQuery->with('residenceAmenity.residence');
                    } elseif ($morphQuery->getModel() instanceof ResidenceAmenity) {
                        $morphQuery->with('residence');
                    }
                },
            ]);

        if ($user->hasRole('Property Management')) {
            $query->where(function ($q) use ($user) {
                $q->whereHasMorph(
                    'maintainable',
                    [ResidenceAmenity::class],
                    fn ($r) => $r->whereHas('residence', fn ($res) => $res->where('property_management_user_id', $user->id))
                )->orWhereHasMorph(
                    'maintainable',
                    [ResidenceAmenityOption::class],
                    fn ($r) => $r->whereHas('residenceAmenity.residence', fn ($res) => $res->where('property_management_user_id', $user->id))
                );
            });
        } elseif ($user->hasRole('Property Management Operation Center')) {
            $residence_ids = get_residence_by_property_management_operation_center($user->id);

            $query->whereHasMorph(
                'maintainable',
                [ResidenceAmenity::class, ResidenceAmenityOption::class],
                function ($subQuery, $type) use ($residence_ids) {
                    if ($type === ResidenceAmenity::class) {
                        $subQuery->whereIn('residence_id', $residence_ids);
                    } elseif ($type === ResidenceAmenityOption::class) {
                        $subQuery->whereHas('residenceAmenity', fn ($q) => $q->whereIn('residence_id', $residence_ids));
                    }
                }
            );
        }

        return $query;
    }
}
