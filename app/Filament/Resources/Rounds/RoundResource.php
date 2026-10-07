<?php

namespace App\Filament\Resources\Rounds;

use App\Filament\Resources\Rounds\Pages\CreateRound;
use App\Filament\Resources\Rounds\Pages\EditRound;
use App\Filament\Resources\Rounds\Pages\ListRounds;
use App\Filament\Resources\Rounds\Pages\ViewRound;
use App\Filament\Resources\Rounds\Schemas\RoundForm;
use App\Filament\Resources\Rounds\Schemas\RoundInfolist;
use App\Filament\Resources\Rounds\Tables\RoundsTable;
use App\Models\Sgoc\Round;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RoundResource extends Resource
{
    protected static ?string $model = Round::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'checkpoint.name';

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Schema $schema): Schema
    {
        return RoundForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return RoundInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RoundsTable::configure($table);
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
            'index' => ListRounds::route('/'),
            'create' => CreateRound::route('/create'),
            'view' => ViewRound::route('/{record}'),
            'edit' => EditRound::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = Round::with(['residence', 'user', 'checkpointRounds', 'checkpoints']);
        $user = auth()->user();

        if ($user->hasRole('Property Management Operation Center')) {
            $residence_ids = get_residence_by_property_management_operation_center($user->id);

            $query->whereIn('mmb_residence_id', $residence_ids);
        } elseif ($user->hasRole('Property Management')) {
            $residence = get_residence_by_property_management($user->id);

            $query->where('mmb_residence_id', $residence->id);
        }

        return $query;
    }
}
