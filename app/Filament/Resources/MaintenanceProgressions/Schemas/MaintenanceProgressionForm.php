<?php

namespace App\Filament\Resources\MaintenanceProgressions\Schemas;

use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MaintenanceProgressionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('maintenance.maintenance_progression'))
                    ->description(__('maintenance.maintenance_progression_detail'))
                    ->columnSpanFull()
                    ->schema([
                        Textarea::make('progress_description')
                            ->label(__('maintenance.progress_description'))
                            ->maxLength(255)
                            ->required(),
                        SpatieMediaLibraryFileUpload::make('progress_image')
                            ->label(__('maintenance.progress_image'))
                            ->collection('maintenance_progression_image')
                            ->customProperties(['type' => 'maintenance_progress'])
                            ->disk('cos')
                            ->openable(true)
                            ->downloadable(true),
                    ])
            ]);
    }
}
