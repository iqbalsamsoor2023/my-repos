<?php

namespace App\Filament\Resources\Events;

use App\Filament\Resources\Events\Pages\CreateEvent;
use App\Filament\Resources\Events\Pages\EditEvent;
use App\Filament\Resources\Events\Pages\ListEvents;
use App\Filament\Resources\Events\RelationManagers\RsvpsRelationManager;
use App\Filament\Resources\Events\Schemas\EventForm;
use App\Filament\Resources\Events\Tables\EventsTable;
use App\Models\Event;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): string
    {
        return __('menu.operation_management');
    }

    public static function getModelLabel(): string
    {
        return __('app.event');
    }

    public static function getPluralModelLabel(): string
    {
        return __('menu.events');
    }

    public static function form(Schema $schema): Schema
    {
        return EventForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EventsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RsvpsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEvents::route('/'),
            'create' => CreateEvent::route('/create'),
            'edit' => EditEvent::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        $query = parent::getEloquentQuery()->select([
            'id',
            'residence_id',
            'title',
            'description',
            'start_at',
            'end_at',
            'is_active',
            'created_at',
            'updated_at',
            'created_by',
        ])->with([
            'residence:id,name,name_th',
        ]);

        if ($user->hasRole('Property Management')) {
            $query->where('residence_id', function ($q) use ($user) {
                $q->select('id')
                    ->from('residences')
                    ->where('property_management_user_id', $user->id)
                    ->limit(1);
            })->where('created_by', $user->id);
        } elseif ($user->hasRole('Property Management Operation Center')) {
            $residence_ids = get_residence_by_property_management_operation_center($user->id);

            $query->whereIn('residence_id', $residence_ids);
        } elseif ($user->hasRole('Developer')) {
            $residenceIds = get_residence_by_developer($user->id);

            if (empty($residenceIds) == false) {
                $query->whereIn('residence_id', $residenceIds)->where('created_by', $user->id);
            } else {
                $query->where('created_by', $user->id);
            }
        }

        return $query;
    }
}
