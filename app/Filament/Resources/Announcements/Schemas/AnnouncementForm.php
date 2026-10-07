<?php

namespace App\Filament\Resources\Announcements\Schemas;

use App\Filament\Resources\Announcements\Pages\CreateAnnouncement;
use App\Filament\Resources\Announcements\Pages\EditAnnouncement;
use App\Models\Unit;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Livewire\Component;

class AnnouncementForm
{
    public static function configure(Schema $schema): Schema
    {
        $user = auth()->user();

        return $schema
            ->components([
                Section::make(__('app.announcement'))
                    ->description(__('app.announcement_details'))
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Fieldset::make(__('app.residence_or_unit'))
                            ->columnSpanFull()
                            ->schema([
                                Toggle::make('all_residence')
                                    ->inline(false)
                                    ->reactive()
                                    ->visible(fn(Component $livewire): bool => $livewire instanceof CreateAnnouncement && $user->hasRole('Developer')),
                                Select::make('residences_id')
                                    ->label(__('app.mooban_or_residence'))
                                    ->multiple()
                                    ->options(function (Component $livewire) {
                                        if ($livewire instanceof CreateAnnouncement) {
                                            return list_create_residences();
                                        }

                                        return list_residences();
                                    })
                                    ->required()
                                    ->reactive()
                                    ->searchable()
                                    ->hidden(fn(Get $get) => $get('all_residence') == true)
                                    ->visible(fn(Component $livewire): bool => $livewire instanceof CreateAnnouncement && $user->hasRole('Developer')),
                                Select::make('residence_id')
                                    ->label(__('app.mooban_or_residence'))
                                    ->options(list_residences())
                                    ->required()
                                    ->reactive()
                                    ->searchable()
                                    ->disabled(fn(Component $livewire): bool => $livewire instanceof EditAnnouncement),
                                Select::make('unit')
                                    ->label(__('unit.unit_number'))
                                    ->multiple()
                                    ->options(function (callable $get) {
                                        return Unit::where('residence_id', $get('residence_id'))->pluck('unit_number', 'id');
                                    })
                            ]),
                        Fieldset::make(__('app.details'))
                            ->columnSpanFull()
                            ->schema([
                                TextInput::make('title')
                                    ->label(__('app.title'))
                                    ->required()
                                    ->maxLength(255),
                                Textarea::make('description')
                                    ->label(__('app.description'))
                                    ->rows(4)
                                    ->required(),
                                SpatieMediaLibraryFileUpload::make('image')
                                    ->label(__('app.image'))
                                    ->multiple()
                                    ->reorderable(true)
                                    ->collection('images')
                                    ->customProperties(['type' => 'image'])
                                    ->disk('cos')
                                    ->image()
                                    ->maxFiles(5)
                                    ->openable(true),
                                SpatieMediaLibraryFileUpload::make('pdf')
                                    ->label(__('PDF'))
                                    ->collection('document')
                                    ->customProperties(['type' => 'document'])
                                    ->disk('cos')
                                    ->acceptedFileTypes(['application/pdf'])
                                    ->openable(true),
                                Toggle::make('is_active')
                                    ->label(__('app.is_active'))
                                    ->inline(false),
                                Hidden::make('created_by')
                                    ->label(__('app.created_by'))
                                    ->default($user->id),
                            ]),
                    ])
            ]);
    }
}
