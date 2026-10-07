<?php

namespace App\Filament\Resources\Maintenances\RelationManagers;

use Filament\Schemas\Schema;
use App\Filament\Resources\MaintenanceProgressions\MaintenanceProgressionResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class MaintenanceProgressionsRelationManager extends RelationManager
{
    protected static string $relationship = 'maintenanceProgressions';

    protected static ?string $recordTitleAttribute = 'progress_description';

    // public static function getTitle(): string
    // {
    //     return __('Maintenance Progressions');
    // }

    public function form(Schema $schema): Schema
    {
        return MaintenanceProgressionResource::getForm($schema);
    }

    public function table(Table $table): Table
    {
        return MaintenanceProgressionResource::getTable($table);
    }
}
