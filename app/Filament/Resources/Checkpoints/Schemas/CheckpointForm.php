<?php

namespace App\Filament\Resources\Checkpoints\Schemas;

use App\Filament\Resources\Checkpoints\Pages\CreateCheckpoint;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Livewire\Component;

class CheckpointForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Checkpoints'))
                    ->description(__('Checkpoint Details'))
                    ->columnSpanFull()
                    ->schema([
                        Fieldset::make(__('checkpoint.patrol_checkpoint'))
                            ->schema([
                                Select::make('mmb_residence_id')
                                    ->label(__('app.mooban_or_residence'))
                                    ->options(function (Component $livewire) {
                                        if ($livewire instanceof CreateCheckpoint) {
                                            return list_create_residences();
                                        }

                                        return list_residences();
                                    })
                                    ->required()
                                    ->reactive()
                                    ->searchable(),
                                TextInput::make('name')
                                    ->label(__('checkpoint.location')),
                                Textarea::make('description')
                                    ->label(__('app.description')),
                                Toggle::make('is_active')
                                    ->label(__('app.is_active'))
                                    ->inline(false),
                            ]),
                    ]),
            ]);
    }
}
