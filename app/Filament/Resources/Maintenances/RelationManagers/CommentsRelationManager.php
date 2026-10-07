<?php

namespace App\Filament\Resources\Maintenances\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CommentsRelationManager extends RelationManager
{
    protected static string $relationship = 'comments';

    protected static ?string $recordTitleAttribute = 'content';

    // public static function getTitle(): string
    // {
    //     return __('Comments');
    // }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('user_id')
                    ->default(Auth()->user()->id),
                Textarea::make('content')
                    ->label(__('app.comment'))
                    ->rows(5)
                    ->cols(5)
                    ->required()
                    ->maxLength(255),
            ])
            ->columns(1);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label(__('app.sender'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('content')
                    ->label(__('app.comment'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('app.reported_at_date'))
                    ->date(),
                TextColumn::make('created_at_time')
                    ->label(__('app.reported_at_time'))
                    ->getStateUsing(function ($record) {
                        return $record->created_at->format('H:i');
                    }),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }
}
