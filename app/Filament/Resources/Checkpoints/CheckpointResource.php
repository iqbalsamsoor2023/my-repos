<?php

namespace App\Filament\Resources\Checkpoints;

use App\Filament\Resources\Checkpoints\Pages\EditCheckpoint;
use App\Filament\Resources\Checkpoints\Pages\ListCheckpoints;
use App\Filament\Resources\Checkpoints\Schemas\CheckpointForm;
use App\Filament\Resources\Checkpoints\Tables\CheckpointsTable;
use App\Models\Sgoc\Checkpoint;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CheckpointResource extends Resource
{
    protected static ?string $model = Checkpoint::class;

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('menu.security_management');
    }

    protected static ?string $slug = 'patrol-guard-checkpoints';

    public static function getNavigationParentItem(): ?string
    {
        return __('menu.securityManagement.manage_patrol_checkpoint_pgs');
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return CheckpointForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CheckpointsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCheckpoints::route('/'),
            'edit' => EditCheckpoint::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $query = parent::getEloquentQuery()->with('residence');

        if ($user->hasRole('Property Management')) {
            $residence = get_residence_by_property_management($user->id);
            $query->where('mmb_residence_id', $residence->id);
        } elseif ($user->hasRole('Property Management Operation Center')) {
            $residence_ids = get_residence_by_property_management_operation_center($user->id);

            $query->whereHas('residence', function (Builder $subQuery) use ($residence_ids) {
                $subQuery->whereIn('id', $residence_ids);
            });
        }

        return $query;
    }
}
