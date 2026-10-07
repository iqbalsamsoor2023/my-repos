<?php

namespace App\Filament\Resources\VisitorRemarks\Schemas;

use App\Filament\Resources\Vms\RelationManagers\VisitorRemarksRelationManager;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Livewire\Component;

class VisitorRemarkForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('menu.visitor_remark'))
                    ->description(__('app.remark'))
                    ->columnSpanFull()
                    ->columns(1)
                    ->schema([
                        Select::make('residence_id')
                            ->label(__('app.mooban_or_residence'))
                            ->relationship('residence', 'name')
                            ->searchable()
                            ->required()
                            ->hidden(fn (Component $livewire): bool => $livewire instanceof VisitorRemarksRelationManager),
                        Textarea::make('remark')
                            ->label(__('app.remark'))
                            ->rows(3)
                            ->cols(3)
                            ->maxLength(255)
                            ->required(),
                    ]),
            ]);
    }
}
