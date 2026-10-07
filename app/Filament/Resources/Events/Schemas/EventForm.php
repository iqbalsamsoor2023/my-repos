<?php

namespace App\Filament\Resources\Events\Schemas;

use App\Filament\Resources\Events\Pages\CreateEvent;
use App\Filament\Resources\Events\Pages\EditEvent;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Livewire\Component;

class EventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('app.event'))
                    ->description(__('app.event_detail'))
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Select::make('residence_id')
                            ->label(__('app.mooban_or_residence'))
                            ->options(function (Component $livewire) {
                                if ($livewire instanceof CreateEvent) {
                                    return list_create_residences();
                                }

                                return list_residences();
                            })
                            ->searchable()
                            ->required()
                            ->columnSpanFull()
                            ->disabled(fn (Component $livewire): bool => $livewire instanceof EditEvent),
                        TextInput::make('title')
                            ->label(__('app.title'))
                            ->required()
                            ->maxLength(255),
                        Toggle::make('is_active')
                            ->label(__('app.is_active'))
                            ->inline(false)
                            ->required(),
                        DateTimePicker::make('start_at')
                            ->label(__('app.start_at'))
                            ->native(false)
                            ->seconds(false)
                            ->disabled(fn (Component $livewire): bool => $livewire instanceof EditEvent),
                        DateTimePicker::make('end_at')
                            ->label(__('app.end_at'))
                            ->native(false)
                            ->seconds(false)
                            ->disabled(fn (Component $livewire): bool => $livewire instanceof EditEvent),
                        Textarea::make('description')
                            ->label(__('app.description'))
                            ->required(),
                        SpatieMediaLibraryFileUpload::make('image')
                            ->label(__('app.image'))
                            ->collection('images')
                            ->disk('cos')
                            ->multiple()
                            ->reorderable(true)
                            ->openable(true),
                        Toggle::make('is_cancel')
                            ->label(__('app.is_cancel'))
                            ->inline(false)
                            ->visible(fn (Component $livewire): bool => $livewire instanceof EditEvent),
                        Hidden::make('created_by')
                            ->label(__('app.created_by'))
                            ->default(Auth()->user()->id),
                    ])
            ]);
    }
}
