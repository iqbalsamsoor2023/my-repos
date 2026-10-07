<?php

namespace App\Filament\Resources\Units\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\Parcels\ParcelResource;
use Filament\Actions\ActionGroup;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ParcelsRelationManager extends RelationManager
{
    protected static string $relationship = 'parcels';

    protected static ?string $recordTitleAttribute = 'tracking_no';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('menu.parcels');
    }

    public function form(Schema $schema): Schema
    {
        return ParcelResource::form($schema);
    }

    public function table(Table $table): Table
    {
        $resource_table = ParcelResource::table($table);

        return $resource_table
                ->headerActions([
                    CreateAction::make()
                        ->modalHeading(__('menu.create_parcel'))
                        ->label(__('menu.new_parcel'))
                        ->mutateDataUsing(function (array $data): array {
                            if ($data['receiver_id'] == 'All') {
                                $data['receiver_id'] = null;
                                $data['receiver_name'] = 'All';
                            } elseif ($data['receiver_id'] == 'Other') {
                                $data['receiver_id'] = null;
                                $data['receiver_name'] = $data['receiver_name'];
                            }

                        $data['receiver_id'] = $data['receiver_id'];
                        $data['receiver_name'] = isset($data['receiver_name']) ? $data['receiver_name'] : null;

                        return $data;
                    })
                    ->after(function (Model $record) {
                        if (isset($record->receiver_id)) {
                            $record->receiver_name = $record->receiver->name;
                        }

                            $record->save();
                        }),
                ])
                ->recordActions([
                    ActionGroup::make([
                        EditAction::make()
                        ->mutateRecordDataUsing(function (Model $record, array $data): array {
                            if ($data['receiver_name'] == 'All') {
                                $data['receiver_id'] = 'All';
                            } elseif ($data['receiver_id'] == null && $data['receiver_name'] != 'All') {
                                $data['receiver_id'] = 'Other';
                            }

                        $data['residence_id'] = $record->unit->residence_id;
                        $data['receiver_id'] = $data['receiver_id'];

                        return $data;
                    })
                    ->using(function (Model $record, array $data): Model {
                        if ($data['receiver_id'] == 'All') {
                            $data['receiver_id'] = null;
                            $data['receiver_name'] = 'All';
                        } elseif ($data['receiver_id'] == 'Other') {
                            $data['receiver_id'] = null;
                            $data['receiver_name'] = $data['receiver_name'];
                        }

                        $data['receiver_id'] = $data['receiver_id'];
                        $data['receiver_name'] = isset($data['receiver_name']) ? $data['receiver_name'] : null;

                        $record->update($data);

                        if (isset($record->receiver_id)) {
                            $record->receiver_name = $record->receiver->name;
                        }

                        $record->save();

                            return $record;
                        }),
                    DeleteAction::make(),
                    ]),
                ], position: RecordActionsPosition::BeforeColumns);
    }
}
