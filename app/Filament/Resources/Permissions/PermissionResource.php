<?php

namespace App\Filament\Resources\Permissions;

use App\Filament\Resources\Permissions\Pages\CreatePermission;
use App\Filament\Resources\Permissions\Pages\EditPermission;
use App\Filament\Resources\Permissions\Pages\ListPermissions;
use App\Models\Permission;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PermissionResource extends Resource
{
    protected static ?string $model = Permission::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static ?int $navigationSort = 15;

    public static function getNavigationGroup(): string
    {
        return __('menu.setting_management');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->hasRole('Super Admin');
    }

    public static function form(Schema $schema): Schema
    {
        return static::getForm($schema);
    }

    public static function getModelLabel(): string
    {
        return __('menu.permission');
    }

    public static function getPluralModelLabel(): string
    {
        return __('menu.permissions');
    }

    public static function table(Table $table): Table
    {
        return static::getTable($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPermissions::route('/'),
            'create' => CreatePermission::route('/create'),
            'edit' => EditPermission::route('/{record}/edit'),
        ];
    }

    public static function getForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Permission')
                    ->label(__('Permission'))
                    ->description(__('Permission Detail'))
                    ->schema([
                        Grid::make([
                            'sm' => 2,
                            'md' => 3,
                            'lg' => 4,
                            'xl' => 6,
                            '2xl' => 6,
                        ])
                            ->schema([
                                Fieldset::make('Permission')
                                    ->schema([
                                        TextInput::make('name')
                                            ->label(__('Name'))
                                            ->required()
                                            ->maxLength(255),
                                        Hidden::make('guard_name')
                                            ->label(__('Guard Name'))
                                            ->default('web'),
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
                    ->label(__('Permissions'))
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label(__('Created at'))
                    ->dateTime(),
            ]);
    }
}
