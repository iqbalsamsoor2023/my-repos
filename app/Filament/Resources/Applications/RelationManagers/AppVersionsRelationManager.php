<?php

namespace App\Filament\Resources\Applications\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TextArea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AppVersionsRelationManager extends RelationManager
{
    protected static string $relationship = 'appVersions';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('build_version')
                    ->required()
                    ->maxLength(255),
                TextInput::make('application_version')
                    ->required()
                    ->maxLength(255),
                Toggle::make('force_update')
                    ->inline(false)
                    ->default(false),
                TextArea::make('release_notes')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('application_name')
            ->columns([
                TextColumn::make('build_version')
                    ->label(__('app.build_version'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('application_version')
                    ->label(__('app.application_version'))
                    ->searchable()
                    ->sortable(),
                ToggleColumn::make('force_update')
                    ->sortable(),
                TextColumn::make('release_notes')
                    ->searchable()
                    ->toggleable()
                    ->toggledHiddenByDefault()
                    ->tooltip(fn ($record) => $record->release_notes ?: __('No release notes available')),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
