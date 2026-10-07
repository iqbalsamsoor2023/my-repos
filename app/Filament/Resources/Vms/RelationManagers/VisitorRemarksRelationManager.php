<?php

namespace App\Filament\Resources\Vms\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use App\Filament\Resources\VisitorRemarks\VisitorRemarkResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class VisitorRemarksRelationManager extends RelationManager
{
    protected static string $relationship = 'visitorRemarks';

    protected static ?string $recordTitleAttribute = 'id';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('menu.visitor_remarks');
    }

    public function form(Schema $schema): Schema
    {
        return VisitorRemarkResource::form($schema);
    }

    public function table(Table $table): Table
    {
        $resource_table = VisitorRemarkResource::table($table);

        return $resource_table
            ->reorderable('sort_order')
            ->reorderRecordsTriggerAction(fn (Action $action): Action => $action->button())
            // Mirrors the order the app receives from VisitorRemarkService.
            ->defaultSort(fn (Builder $query): Builder => $query->orderBy('sort_order')->orderByDesc('id'))
            ->afterReordering(function (): void {
                Notification::make()
                    ->title(__('app.order_updated'))
                    ->success()
                    ->send();
            })
            ->headerActions([
                CreateAction::make()
                    // Put newly created remarks at the end of the current order.
                    ->mutateDataUsing(function (array $data): array {
                        $data['sort_order'] = ((int) $this->getRelationship()->max('sort_order')) + 1;

                        return $data;
                    }),
            ]);
    }
}
