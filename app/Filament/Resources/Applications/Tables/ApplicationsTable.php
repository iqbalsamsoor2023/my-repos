<?php

namespace App\Filament\Resources\Applications\Tables;

use App\Models\AppVersion;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Livewire\Component;

class ApplicationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('platform'),
                TextColumn::make('application_name')
                    ->label(__('app.application_name')),
                TextColumn::make('package_identifier')
                    ->label(__('app.package_identifier')),
                TextColumn::make('build_version')
                    ->label(__('app.build_version'))
                    ->getStateUsing(fn($record) => $record->appVersions->last()->build_version ?? 'N/A'),
                TextColumn::make('application_version')
                    ->label(__('app.application_version'))
                    ->getStateUsing(fn($record) => $record->appVersions->last()->application_version ?? 'N/A'),
                IconColumn::make('force_update')
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->getStateUsing(fn($record) => $record->appVersions->last()->force_update ?? false),
            ])
            ->defaultSort('updated_at', 'desc')
            ->recordActions([
                ActionGroup::make([
                    Action::make('Add New Version')
                        ->button()
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    Hidden::make('application_id')
                                        ->default(fn($record) => $record->id)
                                        ->dehydrated(true),
                                    TextInput::make('package_identifier')
                                        ->default(fn($record) => $record->package_identifier ?? 'com.example.app')
                                        ->disabled()
                                        ->dehydrated(true)
                                        ->required(),
                                    Select::make('platform')
                                        ->options([
                                            'IOS' => __('IOS'),
                                            'Android' => __('Android'),
                                            'Huawei' => __('Huawei'),
                                        ])
                                        ->default(fn($record) => $record->platform ?? 'Android')
                                        ->disabled()
                                        ->dehydrated(true)
                                        ->required(),
                                    TextInput::make('application_name')
                                        ->required()
                                        ->maxLength(255)
                                        ->disabled()
                                        ->dehydrated(true)
                                        ->default(fn($record) => $record->application_name ?? 'MyMooBan'),
                                    TextInput::make('build_version')
                                        ->required()
                                        ->maxLength(255),
                                    TextInput::make('application_version')
                                        ->required()
                                        ->maxLength(255),
                                    Toggle::make('force_update')
                                        ->inline(false)
                                        ->default(false),
                                    Textarea::make('release_notes')
                                        ->columnSpanFull(),
                                ])

                        ])
                        ->modalWidth(Width::Medium)
                        ->action(function (array $data, Component $livewire) {
                            AppVersion::create($data);
                            $livewire->dispatch('notify', [
                                'type' => 'success',
                                'message' => __('app.app_version_created_successfully'),
                            ]);
                            $livewire->dispatch('refreshTable');
                        })
                ]),
            ], position: RecordActionsPosition::BeforeColumns)
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
