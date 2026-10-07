<?php

namespace App\Filament\Resources\Committees\Tables;

use App\Enums\Residence\CommitteeRole;
use App\Models\Committee;
use App\Support\WidgetColorPalette;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Fieldset;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CommitteesTable
{
    public static function configure(Table $table): Table
    {
        $user = Filament::auth()->user();

        return $table
            ->columns([
                TextColumn::make('residence.name')
                    ->label(__('app.mooban_or_residence'))
                    ->toggleable()
                    ->hidden(fn(): bool => $user->hasRole('Property Management'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label(__('app.resident'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('unit.unit_number')
                    ->label(__('unit.unit'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('role')
                    ->label(__('committee.committee_role'))
                    ->badge()
                    ->sortable()
                    ->formatStateUsing(
                        fn (CommitteeRole $state): string => app()->isLocale('th')
                            ? $state->labelTh()
                            : $state->labelEn()
                    )
                    ->color(fn(CommitteeRole $state): array =>
                        WidgetColorPalette::statColorFromHex(
                            WidgetColorPalette::committeeRoles()[$state->value] ?? '#64748B'
                        )
                    ),
                TextColumn::make('term_start')
                    ->label(__('committee.term_start'))
                    ->date('M Y')
                    ->sortable(),
                TextColumn::make('term_end')
                    ->label(__('committee.term_end'))
                    ->date('M Y')
                    ->placeholder(__('committee.active'))
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('app.status'))
                    ->badge()
                    ->getStateUsing(
                        fn(Committee $record): string =>
                        $record->isActive()
                            ? __('committee.active')
                            : __('committee.archived')
                    )
                    ->color(
                        fn(string $state): string =>
                        $state === __('committee.active') ? 'success' : 'danger'
                    ),
            ])
            ->defaultSort('term_start', 'desc')
            ->filters([
                Filter::make('filters')
                    ->columnSpanFull()
                    ->schema([
                        Fieldset::make('Filters')
                            ->columnSpanFull()
                            ->columns(3)
                            ->schema([
                                Select::make('residence')
                                    ->label(__('app.mooban_or_residence'))
                                    ->options(fn () => list_residences())
                                    ->searchable()
                                    ->visible(fn(): bool => $user->hasRole('Super Admin'))
                                    ->columnSpan(1),
                                Select::make('status')
                                    ->label(__('app.status'))
                                    ->options([
                                        'active' => __('committee.active'),
                                        'archived' => __('committee.archived'),
                                    ])
                                    ->searchable()
                                    ->columnSpan(1),

                                Select::make('role')
                                    ->label(__('committee.committee_role'))
                                    ->options(CommitteeRole::class)
                                    ->searchable()
                                    ->columnSpan(1),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $status = $data['status'] ?? null;
                        $role = $data['role'] ?? null;
                        $residence = $data['residence'] ?? null;

                        $query = match ($status) {
                            'active' => $query->active(),
                            'archived' => $query->archived(),
                            default => $query,
                        };

                        if ($role) {
                            $roleValue = $role instanceof CommitteeRole ? $role->value : $role;
                            $query = $query->byRole($roleValue);
                        }

                        if ($residence) {
                            $query = $query->where('residence_id', $residence);
                        }

                        return $query;
                    }),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->persistSortInSession()
            ->persistSearchInSession()
            ->persistFiltersInSession();
    }
}
