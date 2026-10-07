<?php

namespace App\Filament\Resources\Bills\RelationManagers;

use App\Models\Invoice;
use App\Models\Item;
use App\Models\ModelHistory;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class HistoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'histories';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): ?string
    {
        return __('History');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Histories');
    }

    public function table(Table $table): Table
    {
        // Every row below belongs to this invoice, so its number is the same for
        // all of them and needs no lookup per row.
        $invoiceNo = $this->getOwnerRecord()->invoice_no;

        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('user.name')
                    ->label(__('app.name'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('event')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Created' => 'success',
                        'Updated' => 'warning',
                        'Deleted' => 'danger',
                        default => 'gray',
                    })
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('record')
                    ->label(__('billing.record'))
                    ->toggleable()
                    ->getStateUsing(function (ModelHistory $record) use ($invoiceNo) {
                        if ($record->modelable_type === Invoice::class) {
                            return $invoiceNo;
                        }

                        // The item name is already kept on the history row, which
                        // also covers items deleted since the change was made.
                        return data_get($record->old_values, 'name')
                            ?? data_get($record->new_values, 'name')
                            ?? $record->modelable_id;
                    }),
                ViewColumn::make('old_values')
                    ->label(__('billing.old_values'))
                    ->view('tables.columns.history.old-values')
                    ->toggleable(),
                ViewColumn::make('new_values')
                    ->label(__('billing.new_values'))
                    ->view('tables.columns.history.new-values')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('app.created_at'))
                    ->dateTime('d M Y H:i'),
            ]);
    }

    public function getTableQuery(): Builder|Relation
    {
        $ownerRecordId = $this->getOwnerRecord()->id;

        // Grouped so that a search or filter appended by the table is applied to
        // both branches instead of only to the items one.
        return ModelHistory::query()
            ->with('user:id,name')
            ->where(function (Builder $query) use ($ownerRecordId) {
                $query->where(function (Builder $query) use ($ownerRecordId) {
                    $query->where('modelable_type', Invoice::class)
                        ->where('modelable_id', $ownerRecordId);
                })->orWhere(function (Builder $query) use ($ownerRecordId) {
                    $query->where('modelable_type', Item::class)
                        ->whereIn('modelable_id', Item::where('invoice_id', $ownerRecordId)->select('id'));
                });
            });
    }
}
