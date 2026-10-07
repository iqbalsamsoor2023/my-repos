<?php

namespace App\Filament\Resources\Vms\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Actions\CreateAction;
use App\Filament\Resources\VisitorCards\VisitorCardResource;
use App\Helpers\VisitorHelper;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;

class VisitorCardsRelationManager extends RelationManager
{
    protected static string $relationship = 'visitorCards';

    protected static ?string $recordTitleAttribute = 'visitor_card_no';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('menu.visitor_cards');
    }

    public function form(Schema $schema): Schema
    {
        return VisitorCardResource::getForm($schema);
    }

    public function table(Table $table): Table
    {
        $resource_table = VisitorCardResource::getTable($table);

        return $resource_table
            ->headerActions([
                CreateAction::make()
                    ->using(function (Component $livewire, array $data, string $model): Model {
                        $data['visitor_card_no'] = VisitorHelper::generateVisitorCardId($livewire->getRelationship()->getParent()->id);
                        $data['residence_id'] = $livewire->getRelationship()->getParent()->id;

                        return $model::create($data);
                    }),
            ]);
    }
}
