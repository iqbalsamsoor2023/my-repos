<?php

namespace App\Filament\Resources\Calculations\Tables;

use App\Enums\Vehicle\VehicleType;
use App\Models\Calculation;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ColumnGroup;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;

class CalculationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('vehicle_type')
                    ->label(__('vehicle.vehicle_type'))
                    ->formatStateUsing(function (string $state): string {
                        if ($state == VehicleType::CAR->value) {
                            return __('vehicle.'.strtolower(VehicleType::CAR->name));
                        } elseif ($state == VehicleType::MOTORCYCLE->value) {
                            return __('vehicle.'.strtolower(VehicleType::MOTORCYCLE->name));
                        } else {
                            return '-';
                        }
                    }),
                IconColumn::make('is_stamp')
                    ->label(__('visitor.is_stamp'))
                    ->boolean(),
                ViewColumn::make('free_parking_minutes')
                    ->label(__('visitor.free_parking_minutes'))
                    ->view('tables.columns.visitor-parking.free-parking'),
                TextColumn::make('rate_per_hour')
                    ->label(__('visitor.rate_per_hour_thb')),
                ViewColumn::make('chartered_duration')
                    ->label(__('visitor.chartered_duration'))
                    ->view('tables.columns.visitor-parking.chartered-duration'),
                TextColumn::make('chartered_price')
                    ->label(__('visitor.chartered_price')),
                TextColumn::make('penalty')
                    ->label(__('visitor.penalty_thb')),
                ColumnGroup::make(__('app.created_at'), [
                    TextColumn::make('created_at_date')
                        ->label(__('app.date'))
                        ->getStateUsing(function (Model $record) {
                            return $record->created_at->format('d-M-y');
                        }),
                    TextColumn::make('created_at_time')
                        ->label(__('app.time'))
                        ->getStateUsing(function (Model $record) {
                            return $record->created_at->format('H:i:s');
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->after(function (Model $record) {
                            // Runs after the form fields are saved to the database.
                            $calculation = Calculation::where('id', '!=', $record->id)->where('parking_id', $record->parking_id)->where('vehicle_type', $record->vehicle_type)->first();

                            if ($calculation) {
                                $calculation->update(['penalty' => $record->penalty]);
                            }
                        }),
                    DeleteAction::make()
                        ->visible(fn (Component $livewire): bool => auth()->user()->hasRole(['Super Admin', 'Admin'])),
                ]),
            ], position: RecordActionsPosition::BeforeColumns)
            ->headerActions([
                CreateAction::make()
                    ->label(__('menu.new_calculation'))
                    ->using(function (Component $livewire, array $data): Model {
                        $calculation = Calculation::where('parking_id', $livewire->ownerRecord->id)->where('vehicle_type', $data['vehicle_type'])->first();

                        if ($calculation) {
                            $data['penalty'] = $calculation->penalty;
                        }

                        return $livewire->getRelationship()->create($data);
                    }),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }
}
