<?php

namespace App\Filament\Resources\VisitorPurposes\Schemas;

use App\Filament\Resources\Vms\RelationManagers\VisitorPurposesRelationManager;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Livewire\Component;

class VisitorPurposeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Purpose of Visit'))
                    ->description(__('Purpose Detail'))
                    ->columnSpanFull()
                    ->columns(1)
                    ->schema([
                        Select::make('residence_id')
                            ->label(__('app.mooban_or_residence'))
                            ->relationship('residence', 'name')
                            ->searchable()
                            ->required()
                            ->hidden(fn(Component $livewire): bool => $livewire instanceof VisitorPurposesRelationManager),
                        Textarea::make('purpose')
                            ->rows(3)
                            ->cols(3)
                            ->maxLength(255)
                            ->required(),
                    ])
            ]);
    }
}
