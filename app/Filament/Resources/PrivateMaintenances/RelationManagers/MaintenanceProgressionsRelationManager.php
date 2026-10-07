<?php

namespace App\Filament\Resources\PrivateMaintenances\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Actions\CreateAction;
use App\Enums\Maintenance\MaintenanceStatus;
use App\Filament\Resources\MaintenanceProgressions\MaintenanceProgressionResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;

class MaintenanceProgressionsRelationManager extends RelationManager
{
    protected static string $relationship = 'maintenanceProgressions';

    protected static ?string $recordTitleAttribute = 'maintenance_id';

    public function form(Schema $schema): Schema
    {
        return MaintenanceProgressionResource::form($schema);
    }

    public function table(Table $table): Table
    {
        $progression_resource = MaintenanceProgressionResource::table($table);

        return $progression_resource
                ->headerActions([
                    CreateAction::make()
                        ->label(__('menu.new_maintenance_progression'))
                        ->modalHeading(__('menu.create_maintenance_progression'))
                        ->using(function (Component $livewire, array $data): Model {
                            $parent = $livewire->getRelationship()->getParent();
                            $parent->update(['status' => MaintenanceStatus::IN_PROGRESS->value]);

                        return $livewire->getRelationship()->create($data);
                    }),
            ]);
    }
}
