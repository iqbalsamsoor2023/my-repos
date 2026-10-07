<?php

namespace App\Filament\Resources\Audits\Tables;

use App\Enums\Audit\EventTypeEnum;
use App\Models\Audit;
use App\Models\User;
use App\Services\GeoLocationService;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\ColumnGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use hisorange\BrowserDetect\Parser as Browser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AuditsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('event')
                    ->label(__('app.event'))
                    ->badge()
                    ->formatStateUsing(function ($state) {
                        return EventTypeEnum::tryFrom($state)?->label() ?? $state;
                    })
                    ->color(function ($state) {
                        return EventTypeEnum::tryFrom($state)?->color() ?? 'gray';
                    }),
                TextColumn::make('user.name')
                    ->label(__('app.name'))
                    ->description(fn(Audit $record): string => $record?->user?->email ?? '-')
                    ->copyable()
                    ->toggleable(),
                TextColumn::make('auditable_type')
                    ->label(__('app.auditable_type')),
                TextColumn::make('auditable_id')
                    ->label(__('app.auditable_id')),
                TextColumn::make('old_values')
                    ->label(__('Old Values'))
                    ->formatStateUsing(function ($record) {
                        $decoded = json_decode($record->old_values, true);

                        if (! is_array($decoded)) {
                            return 'Invalid data';
                        }

                        if (
                            $record->event === 'deleted' &&
                            $record->auditable_type === User::class
                        ) {
                            $decoded = [
                                'name' => $decoded['name'] ?? null,
                                'email' => $decoded['email'] ?? null,
                            ];
                        }

                        return collect($decoded)
                            ->map(fn($value, $key) => "<strong>{$key}</strong>: " . (
                                is_bool($value)
                                ? ($value ? 'true' : 'false')
                                : (is_null($value) ? 'null' : e($value))
                            ))
                            ->implode('<br>');
                    })
                    ->html()
                    ->html()
                    ->toggleable(),
                TextColumn::make('new_values')
                    ->label(__('New Values'))
                    ->formatStateUsing(function ($record) {
                        $decoded = json_decode($record->new_values, true);

                        if (! is_array($decoded)) {
                            return 'No data';
                        }

                        return collect($decoded)
                            ->map(fn($value, $key) => "<strong>{$key}</strong>: " . (
                                is_bool($value)
                                ? ($value ? 'true' : 'false')
                                : (is_null($value) ? 'null' : e($value))
                            ))
                            ->implode('<br>');
                    })
                    ->html()
                    ->toggleable(),
                TextColumn::make('ip_address')
                    ->label(__('checkpoint.location'))
                    ->formatStateUsing(function ($state) {
                        return GeoLocationService::getLocationFromIp($state);
                    }),
                TextColumn::make('user_agent')
                    ->label(__('app.device_info'))
                    ->formatStateUsing(function ($state) {
                        if (empty($state)) {
                            return 'Unknown';
                        }

                        $parser = new Browser;
                        $result = $parser->parse($state);

                        return $result->platformName() . ' - ' . $result->browserName();
                    })
                    ->tooltip(fn($state) => $state)
                    ->wrap(),
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
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('event')
                    ->label(__('app.event'))
                    ->options([
                        'created' => 'Created',
                        'updated' => 'Updated',
                        'deleted' => 'Deleted',
                        'exported' => 'Exported',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['value'],
                                fn(Builder $query): Builder => $query->where('event', $data['value']),
                            );
                    }),
                SelectFilter::make('auditable_type')
                    ->label(__('app.auditable_type'))
                    ->options(function (): array {
                        return Audit::query()
                            ->select('auditable_type')
                            ->whereNotNull('auditable_type')
                            ->distinct()
                            ->orderBy('auditable_type')
                            ->pluck('auditable_type')
                            ->mapWithKeys(function (string $class): array {
                                return [
                                    $class => Str::headline(class_basename($class)),
                                ];
                            })
                            ->toArray();
                    })
                    ->searchable(),
                Filter::make('name')
                    ->schema([
                        TextInput::make('name')
                            ->label(__('app.name')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            filled($data['name'] ?? null),
                            fn(Builder $query) => $query->whereHas(
                                'userModel',
                                fn(Builder $q) => $q->where(
                                    'name',
                                    'LIKE',
                                    '%' . $data['name'] . '%'
                                )
                            )
                        );
                    }),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('created_from')
                            ->label(__('app.created_from')),
                        DatePicker::make('created_until')
                            ->label(__('app.created_until')),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (isset($data['created_from']) && isset($data['created_until'])) {
                            return $query
                                ->when(
                                    $data['created_from'],
                                    fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                                )
                                ->when(
                                    $data['created_until'],
                                    fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                                );
                        }
                    }),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                ]),
            ], position: RecordActionsPosition::BeforeColumns);
    }
}
