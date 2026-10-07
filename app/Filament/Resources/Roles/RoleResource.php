<?php

namespace App\Filament\Resources\Roles;

use App\Filament\Resources\Roles\Pages\CreateRole;
use App\Filament\Resources\Roles\Pages\EditRole;
use App\Filament\Resources\Roles\Pages\ListRoles;
use App\Models\Role;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Livewire\Component as Livewire;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bars-4';

    protected static ?int $navigationSort = 14;

    public static function getNavigationLabel(): string
    {
        return __('menu.roles');
    }

    public static function getNavigationGroup(): string
    {
        return __('menu.setting_management');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->hasRole('Super Admin');
    }

    public static function canAccess(): bool
    {
        if (auth()->user()->hasAnyRole(['Super Admin'])) {
            return true;
        }

        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return static::getForm($schema);
    }

    public static function table(Table $table): Table
    {
        return static::getTable($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'edit' => EditRole::route('/{record}/edit'),
        ];
    }

    public static function getForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('user.role'))
                    ->description(__('app.role_details'))
                    ->schema([
                        Grid::make([
                            'sm' => 2,
                            'md' => 3,
                            'lg' => 4,
                            'xl' => 6,
                            '2xl' => 6,
                        ])
                            ->schema([
                                Fieldset::make(__('user.role'))
                                    ->schema([
                                        Group::make()
                                            ->schema([
                                                TextInput::make('name')
                                                    ->label(__('app.name'))
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->disabled(fn (Livewire $livewire): bool => $livewire instanceof EditRole),
                                            ]),
                                        Hidden::make('guard_name')
                                            ->default('web'),
                                        Section::make()
                                            ->schema([
                                                CheckboxList::make('permissions')
                                                    ->label(__('app.assign_permissions'))
                                                    ->relationship('permissions', 'name')
                                                    ->required()
                                                    ->columns(1),
                                            ]),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    public static function getTable(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('user.roles')),
                TextColumn::make('permissions.name'),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                ]),
            ], position: RecordActionsPosition::BeforeColumns);
    }
}
