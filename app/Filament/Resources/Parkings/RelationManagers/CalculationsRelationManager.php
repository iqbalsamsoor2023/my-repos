<?php

namespace App\Filament\Resources\Parkings\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Actions\CreateAction;
use App\Filament\Resources\Calculations\CalculationResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class CalculationsRelationManager extends RelationManager
{
    protected static string $relationship = 'calculations';

    protected static ?string $recordTitleAttribute = 'parking_id';

    public function form(Schema $schema): Schema
    {
        return CalculationResource::form($schema);
    }

    public function table(Table $table): Table
    {
        $calculationResource = CalculationResource::table($table);

        return $calculationResource
            ->headerActions([
                CreateAction::make()
                    ->modalHeading(__('menu.create_calculation'))
                    ->label(__('menu.new_calculation')),
            ]);
    }
}
