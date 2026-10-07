<?php

namespace App\Filament\Resources\Vms\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Actions\CreateAction;
use App\Filament\Resources\VisitorSettings\VisitorSettingResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class VisitorSettingRelationManager extends RelationManager
{
    protected static string $relationship = 'visitorSetting';

    protected static ?string $recordTitleAttribute = 'id';

    // public static function getTitle(): string
    // {
    //     return __('Visitor Settings');
    // }

    public function form(Schema $schema): Schema
    {
        return VisitorSettingResource::form($schema);
    }

    public function table(Table $table): Table
    {
        $resource_table = VisitorSettingResource::table($table);

        return $resource_table
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
