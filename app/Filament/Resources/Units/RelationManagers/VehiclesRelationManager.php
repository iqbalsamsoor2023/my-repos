<?php

namespace App\Filament\Resources\Units\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\Vehicles\VehicleResource;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use Filament\Actions\ActionGroup;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class VehiclesRelationManager extends RelationManager
{
    protected static string $relationship = 'vehicles';

    protected static ?string $recordTitleAttribute = 'unit_id';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('menu.vehicles');
    }

    public function form(Schema $schema): Schema
    {
        return VehicleResource::form($schema);
    }

    public function table(Table $table): Table
    {
        $resource_table = VehicleResource::table($table);

        return $resource_table
            ->filtersFormColumns(3)
            ->headerActions([
                CreateAction::make()
                    ->modalHeading(__('menu.create_vehicle'))
                    ->label(__('menu.new_vehicle'))
                    ->mutateDataUsing(function (array $data): array {
                        if ($data['brand_id'] == 'Other') {
                            $brand = VehicleBrand::where('name', 'LIKE', '%' . $data['vehicle_brand'] . '%')->first();

                            if (! $brand) {
                                $brand = VehicleBrand::create([
                                    'name' => $data['vehicle_brand'],
                                ]);
                            }

                            $data['brand_id'] = $brand->id;
                        } else {
                            $brand = VehicleBrand::whereId($data['brand_id'])->first();
                        }

                        if ($data['vehicle_model_id'] == 'Other') {
                            $vehicle_model = VehicleModel::where('name', $data['vehicle_model'])->first();

                            if (! $vehicle_model) {
                                $vehicle_model = VehicleModel::create([
                                    'brand_id' => $brand->id,
                                    'name' => $data['vehicle_model'],
                                    'type' => $data['type'],
                                ]);
                            }

                            $data['vehicle_model_id'] = $vehicle_model->id;
                        }

                        return $data;
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->mutateRecordDataUsing(function (Model $record, array $data): array {
                            $data['residence_id'] = $record->unit->residence->id;
                            $data['unit_id'] = $record->unit_id;
                            $data['type'] = $record->vehicleModel->type ?? null;
                            $data['vehicle_brand_id'] = $record->vehicleModel->vehicle_brand_id ?? null;

                            return $data;
                        })
                        ->using(function (Model $record, array $data): Model {
                            if ($data['brand_id'] == 'Other') {
                                $brand = VehicleBrand::where('name', 'LIKE', '%'.$data['vehicle_brand'].'%')->first();
    
                                if (! $brand) {
                                    $brand = VehicleBrand::create([
                                        'name' => $data['vehicle_brand'],
                                    ]);
                                }
    
                                $data['brand_id'] = $brand->id;
                            } else {
                                $brand = VehicleBrand::whereId($data['brand_id'])->first();
                            }
    
                            if ($data['vehicle_model_id'] == 'Other') {
                                $vehicle_model = VehicleModel::where('name', $data['vehicle_model'])->first();
    
                                if (! $vehicle_model) {
                                    $vehicle_model = VehicleModel::create([
                                        'brand_id' => $brand->id,
                                        'name' => $data['vehicle_model'],
                                        'type' => $data['type'],
                                    ]);
                                }
    
                                $data['vehicle_model_id'] = $vehicle_model->id;
                            }
    
                            $record->update($data);
                            $record->save();
    
                            return $record;
                        }),
                    DeleteAction::make(),
                ]),
            ], position: RecordActionsPosition::BeforeColumns);
    }
}
