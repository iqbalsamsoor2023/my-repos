<?php

namespace App\Filament\Resources\Rounds\Tables;

use App\Models\Sgoc\Round;
use Carbon\Carbon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class RoundsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('round_number')
                    ->label(__('checkpoint.round_number'))
                    ->sortable(false)
                    ->searchable(),
                TextColumn::make('user.email')
                    ->label(__('user.guard_talk_account'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('start_time')
                    ->label(__('checkpoint.start_time_end_time'))
                    ->sortable()
                    ->searchable()
                    ->formatStateUsing(fn(string $state, Round $record): string => Carbon::parse($state)->format('H:i') . ' - ' . Carbon::parse($record?->end_time)->format('H:i')),
                TextColumn::make('checkpoint_list')
                    ->label(__('checkpoint.checkpoints'))
                    ->html()
                    ->state(function (Round $record): string {
                        $checkpointRounds = $record?->relationLoaded('checkpointRounds')
                            ? $record?->checkpointRounds
                            : $record?->checkpointRounds()->with('checkpoint.zone')->get();

                        // Group by zone id
                        $grouped = $checkpointRounds->groupBy(fn($cr) => $cr->checkpoint->zone?->id ?? 0);

                        $output = [];

                        foreach ($grouped as $checkpoints) {
                            // Get zone info
                            $zone = $checkpoints->first()->checkpoint->zone ?? null;
                            $zoneName = $zone ? "Zone {$zone->zone_number} - {$zone->name}" : "Zone 0 - No Zone";
                            $output[] = "<strong>{$zoneName}</strong>";

                            // Deduplicate by checkpoint ID and sort by sequence
                            $uniqueCheckpoints = $checkpoints
                                ->unique(fn($cr) => $cr->checkpoint->id)
                                ->sortBy('sequence')
                                ->values(); // reset keys to 0,1,2…

                            // Numbering starts from 1 per zone
                            foreach ($uniqueCheckpoints as $index => $cr) {
                                $output[] = ($index + 1) . ". " . $cr->checkpoint->name;
                            }

                            // Add extra line break between zones
                            $output[] = "";
                        }

                        return $output ? implode('<br>', $output) : '-';
                    })
                    ->searchable(false)
                    ->sortable(false),
                TextColumn::make('checkpoints_count')
                    ->label(__('checkpoint.checkpoint_count'))
                    ->getStateUsing(fn (Round $record) =>
                        $record->checkpoints()->count() ?? 0
                    )
                    ->sortable()
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                // ViewAction::make(),
                // EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // DeleteBulkAction::make(),
                    // ForceDeleteBulkAction::make(),
                    // RestoreBulkAction::make(),
                ]),
            ]);
    }
}
