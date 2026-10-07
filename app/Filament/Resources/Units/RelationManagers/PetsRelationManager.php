<?php

namespace App\Filament\Resources\Units\RelationManagers;

use App\Filament\Resources\Pets\PetResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PetsRelationManager extends RelationManager
{
    protected static string $relationship = 'pets';

    protected static ?string $recordTitleAttribute = 'breed';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('menu.assetManagement.manage_pets');
    }

    public function form(Schema $schema): Schema
    {
        return PetResource::form($schema);
    }

    public function table(Table $table): Table
    {
        $petResource = PetResource::table($table);

        return $petResource
            ->headerActions([
                CreateAction::make()
                    ->modalHeading(__('menu.create_pet'))
                    ->label(__('menu.new_pet')),
            ]);
    }
}
