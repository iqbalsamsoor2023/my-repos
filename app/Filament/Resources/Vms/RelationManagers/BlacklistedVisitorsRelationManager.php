<?php

namespace App\Filament\Resources\Vms\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\BlacklistVisitors\BlacklistVisitorResource;
use App\Models\Visitor;
use Filament\Actions\ActionGroup;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;

class BlacklistedVisitorsRelationManager extends RelationManager
{
    protected static string $relationship = 'blacklistedVisitors';

    protected static ?string $recordTitleAttribute = 'id';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('menu.blacklisted_visitors');
    }

    public function form(Schema $schema): Schema
    {
        return BlacklistVisitorResource::form($schema);
    }

    public function table(Table $table): Table
    {
        $resource_table = BlacklistVisitorResource::table($table);

        return $resource_table
            ->headerActions([
                CreateAction::make()
                    ->using(function (Component $livewire, array $data): Model {
                        $visitor = Visitor::where('id_number', $data['id_number'])->first();

                        if (! $visitor) {
                            $visitor = Visitor::create([
                                'name' => $data['name'],
                                'id_type' => $data['id_type'],
                                'id_number' => $data['id_number'],
                            ]);
                        }

                        return $livewire->getRelationship()->create(array_merge($data, [
                            'visitor_id' => $visitor->id,
                        ]));
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->mutateRecordDataUsing(function (Model $record, array $data): array {
                            $data['name'] = $record->visitor->name;
                            $data['id_type'] = $record->visitor->id_type;
                            $data['id_number'] = $record->visitor->id_number;

                            return $data;
                        })
                        ->using(function (Model $record, array $data): Model {
                            $record->visitor->update($data);
                            $record->update($data);

                            return $record;
                        }),
                    DeleteAction::make(),
                ]),
            ], position: RecordActionsPosition::BeforeColumns);
    }
}
