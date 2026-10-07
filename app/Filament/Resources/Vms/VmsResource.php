<?php

namespace App\Filament\Resources\Vms;

use App\Filament\Resources\Vms\Pages\EditVms;
use App\Filament\Resources\Vms\Pages\ListVms;
use App\Filament\Resources\Vms\RelationManagers\BlacklistedVisitorsRelationManager;
use App\Filament\Resources\Vms\RelationManagers\VisitorCardsRelationManager;
use App\Filament\Resources\Vms\RelationManagers\VisitorPurposesRelationManager;
use App\Filament\Resources\Vms\RelationManagers\VisitorRemarksRelationManager;
use App\Filament\Resources\Vms\RelationManagers\VisitorSettingRelationManager;
use App\Filament\Resources\Vms\Schemas\VmsForm;
use App\Models\Residence;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VmsResource extends Resource
{
    protected static ?string $model = Residence::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard';

    protected static ?string $slug = 'vms-managements';

    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationLabel(): string
    {
        return __('menu.visitor_managements');
    }

    public static function getNavigationGroup(): string
    {
        return __('menu.security_data');
    }

    public static function getModelLabel(): string
    {
        return __('Visitor Management');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Visitor Managements');
    }

    public static function form(Schema $schema): Schema
    {
        return VmsForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return static::getTable($table);
    }

    public static function getRelations(): array
    {
        return [
            VisitorSettingRelationManager::class,
            VisitorCardsRelationManager::class,
            VisitorPurposesRelationManager::class,
            VisitorRemarksRelationManager::class,
            BlacklistedVisitorsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVms::route('/'),
            'edit' => EditVms::route('/{record}/edit'),
        ];
    }

    public static function getTable(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->translateLabel()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('name_th')
                    ->label(__('Name (TH)'))
                    ->searchable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        $query = parent::getEloquentQuery()
            ->select([
                'id',
                'name',
                'name_th',
                'property_management_user_id',
                'created_at',
            ]);

        if ($user->hasRole('Property Management')) {
            $query->where('property_management_user_id', $user->id);
        } elseif ($user->hasRole('Property Management Operation Center')) {
            $residence_ids = get_residence_by_property_management_operation_center($user->id);

            $query->whereIn('id', $residence_ids);
        }

        return $query;
    }
}
