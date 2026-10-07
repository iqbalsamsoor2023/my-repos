<?php

namespace App\Filament\Resources\ProductOperationGuides\Tables;

use App\Enums\ResourceMaterial\ResourceTypeEnum;
use App\Models\Erp\Platform;
use App\Models\Erp\ResourceMaterial;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\App;

class ProductOperationGuidesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->query(ResourceMaterial::where('type', ResourceTypeEnum::PRODUCT_OPERATION_GUIDE->value))
            ->columns([
                TextColumn::make('platform.name')
                    ->label(__('app.platform'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('title')
                    ->label(__('app.title'))
                    ->formatStateUsing(function ($state, $record) {
                        return $record->getTranslation('title', App::getLocale());
                    })
                    ->toggleable(),
                IconColumn::make('source_link')
                    ->label(__('Source Link'))
                    ->icon(fn (string $state): string => match ($state) {
                        default => 'heroicon-o-document',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        default => 'primary',
                    })
                    ->url(function (ResourceMaterial $record) {
                        return $record->source_link ?? '';
                    })
                    ->tooltip('Click here')
                    ->openUrlInNewTab(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                SelectFilter::make('platform_id')
                    ->label(__('app.platform'))
                    ->options(Platform::all()->pluck('name', 'id')),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->visible(auth()->user()->hasAnyRole(['Super Admin', 'Admin'])),
                    DeleteAction::make()
                        ->visible(auth()->user()->hasAnyRole(['Super Admin', 'Admin'])),
                ]),
            ], position: RecordActionsPosition::BeforeColumns)
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(auth()->user()->hasAnyRole(['Super Admin'])),
                ]),
            ]);
    }
}
