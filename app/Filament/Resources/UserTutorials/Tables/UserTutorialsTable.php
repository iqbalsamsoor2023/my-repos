<?php

namespace App\Filament\Resources\UserTutorials\Tables;

use App\Enums\ResourceMaterial\ResourceTypeEnum;
use App\Models\Erp\Platform;
use App\Models\Erp\ResourceMaterial;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\App;

class UserTutorialsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->query(ResourceMaterial::where('type', ResourceTypeEnum::USER_TUTORIAL->value))
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
                        default => 'heroicon-o-video-camera',
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
            ->filters([
                SelectFilter::make('platform_id')
                    ->label(__('app.platform'))
                    ->options(Platform::all()->pluck('name', 'id')),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ], position: RecordActionsPosition::BeforeColumns)
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }
}
