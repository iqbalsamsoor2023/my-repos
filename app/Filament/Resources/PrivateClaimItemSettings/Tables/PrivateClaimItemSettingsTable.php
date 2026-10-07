<?php

namespace App\Filament\Resources\PrivateClaimItemSettings\Tables;

use App\Filament\Resources\PrivateClaimItemSettings\Pages\ListPrivateClaimItemSettings;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class PrivateClaimItemSettingsTable
{
    public static function configure(Table $table): Table
    {
        $user = auth()->user();

        return $table
            ->columns([
                TextColumn::make('residence.name')
                    ->label(__('app.residence'))
                    ->sortable()
                    ->searchable(),
                    TextColumn::make('privateClaimItem.display_name')
                    ->label(__('maintenance.private_claim_item'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('warranty_period')
                    ->label(__('maintenance.warranty_period'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('supplier_company_name')
                    ->label(__('maintenance.supplier_company_name'))
                    ->searchable(),
                TextColumn::make('supplier_company_name_th')
                    ->label(__('maintenance.supplier_company_name_th'))
                    ->searchable(),
                TextColumn::make('supplier_item_brand')
                    ->label(__('maintenance.supplier_item_brand'))
                    ->searchable(),
                TextColumn::make('pic_name')
                    ->label(__('app.name'))
                    ->searchable(),
                TextColumn::make('pic_mobile_no')
                    ->label(__('app.contact_no'))
                    ->searchable(),
                TextColumn::make('pic_email')
                    ->label(__('app.email'))
                    ->searchable(),
                IconColumn::make('is_out_warranty')
                    ->label(__('maintenance.is_out_warranty'))
                    ->boolean(),
                TextColumn::make('remark')
                    ->label(__('app.remark'))
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label(__('app.created_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label(__('app.updated_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('residence')
                    ->label(__('app.mooban_or_residence'))
                    ->options(list_residences())
                    ->searchable()
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['value'],
                                fn (Builder $query): Builder => $query->whereHas('residence', function ($q) use ($data) {
                                    return $q->whereId($data['value']);
                                }),
                            );
                    })
                    ->visible(fn (Component $livewire): bool => $livewire instanceof ListPrivateClaimItemSettings && $user->hasRole(['Super Admin', 'Admin', 'Property Management Operation Center'])),
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
                                    fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                                )
                                ->when(
                                    $data['created_until'],
                                    fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                                );
                        }
                    }),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                ]),
            ], position: RecordActionsPosition::BeforeColumns)
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
