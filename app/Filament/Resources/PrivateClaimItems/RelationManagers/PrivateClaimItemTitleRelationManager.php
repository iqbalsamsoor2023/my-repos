<?php

namespace App\Filament\Resources\PrivateClaimItems\RelationManagers;

use App\Filament\Resources\PrivateClaimItems\PrivateClaimItemResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PrivateClaimItemTitleRelationManager extends RelationManager
{
    protected static string $relationship = 'privateClaimItemTitle';

    protected static ?string $relatedResource = PrivateClaimItemResource::class;

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Claim Item Title Information')
                    ->schema([
                        Select::make('private_claim_item_id')
                            ->label('Claim Item')
                            ->relationship('privateClaimItem', 'name')
                            ->required(),
    
                        // SHOW ONLY ON CREATE
                        Repeater::make('titles')
                            ->schema([
                                TextInput::make('name')->required()->maxLength(255),
                                TextInput::make('name_th')->maxLength(255),
                            ])
                            ->columns(2)
                            ->defaultItems(1)
                            ->addActionLabel('Add another title')
                            // ->visible(fn ($livewire) => $livewire instanceof \Filament\Resources\Pages\CreateRecord),
                            ->visible(fn (string $operation) => $operation === 'create'),
    
                        // SHOW ONLY ON EDIT
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            // ->visible(fn ($livewire) => $livewire instanceof \Filament\Resources\Pages\EditRecord),
                            ->visible(fn (string $operation) => $operation === 'edit'),

                        TextInput::make('name_th')
                            ->maxLength(255)
                            // ->visible(fn ($livewire) => $livewire instanceof \Filament\Resources\Pages\EditRecord),
                            ->visible(fn (string $operation) => $operation === 'edit'),
    
                    ])
                    ->columns(1),
            ])
            ->columns(1);
    }

    public function table(Table $table): Table
    {
        return $table
        ->columns([
            TextColumn::make('privateClaimItem.name')
                ->numeric()
                ->sortable(),
            TextColumn::make('name')
                ->label(__('app.name'))
                ->searchable(),
            TextColumn::make('name_th')
                ->label(__('app.name_th'))
                ->searchable(),
            TextColumn::make('created_at')
                ->label(__('app.created_at'))
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('updated_at')
                ->label(__('app.updated_at'))
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('deleted_at')
                ->label(__('app.deleted_at'))
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ])
        ->filters([
            //
        ])
        ->headerActions([
            CreateAction::make(),
        ])
        ->recordActions([
            EditAction::make(),
        ])
        ->toolbarActions([
            BulkActionGroup::make([
                DeleteBulkAction::make(),
            ]),
        ]);
    }
}