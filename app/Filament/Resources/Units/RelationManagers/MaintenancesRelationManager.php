<?php

namespace App\Filament\Resources\Units\RelationManagers;

use App\Filament\Resources\Maintenances\MaintenanceResource;
use App\Filament\Resources\PrivateMaintenances\Pages\ListPrivateMaintenances;
use App\Models\Amenity;
use App\Models\Maintenance;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class MaintenancesRelationManager extends RelationManager
{
    protected static string $relationship = 'maintenances';

    protected static ?string $recordTitleAttribute = 'maintainable_type';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('menu.private_maintenances');
    }

    public function form(Schema $schema): Schema
    {
        return MaintenanceResource::form($schema);
    }

    public function table(Table $table): Table
    {
        $resource_table = MaintenanceResource::table($table);
        $maintenances = new ListPrivateMaintenances;

        return $resource_table
            ->headerActions([
                CreateAction::make()
                    ->modalHeading(__('menu.create_private_maintenance'))
                    ->label(__('menu.new_private_maintenance'))
                    ->using(function (Component $livewire, array $data): Model {
                        if ($data['amenity'] != 'Others') {
                            $amenity = Amenity::whereId($data['amenity'])->first();
                        } else {
                            $unit = $livewire->getRelationship()->getParent();
                            $amenity = Amenity::where('amenity_name', 'LIKE', '%'.$data['amenity_name'].'%')->where('residence_id', $unit->residence_id)->first();

                            if (! $amenity) {
                                $amenity = new Amenity;
                                $amenity->residence_id = $unit->residence_id;
                                $amenity->amenity_name = $data['amenity_name'];
                                $amenity->save();
                            }
                        }

                        $data['amenity'] = [
                            'id' => $amenity->id,
                            'amenity_name' => $amenity->amenity_name,
                            'warranty_period' => $amenity->warranty_period,
                            'period_type' => $amenity->period_type,
                            'supplier' => $amenity->supplier,
                            'is_out_warranty' => $amenity->is_out_warranty,
                            'remark' => $amenity->remark,
                        ];
                        $data['reported_by'] = Auth::user()->id;

                        return $livewire->getRelationship()->create($data);
                    }),
            ])
            ->filters($maintenances->getTableFilters())
            ->filtersFormColumns(3)
            ->recordActions([
                ActionGroup::make([
                    Action::make('view')
                        ->icon('heroicon-o-eye')
                        ->url(fn (Maintenance $record): string => route('filament.admin.resources.private-maintenances.view', $record))
                        ->openUrlInNewTab(),
                    Action::make('edit')
                        ->icon('heroicon-o-pencil')
                        ->url(fn (Maintenance $record): string => route('filament.admin.resources.private-maintenances.edit', $record))
                        ->openUrlInNewTab(),
                    DeleteAction::make(),
                ]),
            ], position: RecordActionsPosition::BeforeColumns);
    }
}
