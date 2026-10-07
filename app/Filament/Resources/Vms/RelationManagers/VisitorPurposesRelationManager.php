<?php

namespace App\Filament\Resources\Vms\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Actions\CreateAction;
use App\Filament\Resources\VisitorPurposes\VisitorPurposeResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class VisitorPurposesRelationManager extends RelationManager
{
    protected static string $relationship = 'visitorPurposes';

    protected static ?string $recordTitleAttribute = 'purpose';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('menu.visitor_purposes');
    }

    public function form(Schema $schema): Schema
    {
        return VisitorPurposeResource::form($schema);
    }

    public function table(Table $table): Table
    {
        $resource_table = VisitorPurposeResource::table($table);

        return $resource_table
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
