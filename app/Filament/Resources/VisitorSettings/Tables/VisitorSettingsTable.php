<?php

namespace App\Filament\Resources\VisitorSettings\Tables;

use App\Filament\Resources\Vms\RelationManagers\VisitorSettingRelationManager;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Livewire\Component;

class VisitorSettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('residence.name')
                    ->label(__('Residence/MooBan'))
                    ->searchable()
                    ->hidden(fn (Component $livewire): bool => $livewire instanceof VisitorSettingRelationManager),
                // IconColumn::make('is_qr_active')
                //     ->boolean(),
                // IconColumn::make('is_qr_code_scan_out')
                //     ->getStateUsing(function (Model $record) {
                //         $activationModule = ActivationModule::where('residence_id', $record->residence_id)
                //             ->where('module_type', ModuleType::VS_QR_SCAN_OUT->value)
                //             ->first();

                //         if (is_null($activationModule) == false) {
                //             return $activationModule->is_active;
                //         } else {
                //             return false;
                //         }
                //     })
                //     ->boolean(),
                ImageColumn::make('pdpa')
                    ->label(__('app.pdpa'))
                    ->getStateUsing(function ($record) {
                        $media = $record->getFirstMedia('document');

                        if (! $media) {
                            return null;
                        }

                        if (in_array($media->mime_type, ['application/pdf'])) {
                            return asset('images/file-type-icon/pdf.png');
                        }

                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    // ->mutateRecordDataUsing(function (array $data, HasRelationshipTable $livewire): array {
                    //     $activation_module = ActivationModule::where('residence_id', $livewire->getRelationship()->getParent()->id)
                    //                                         ->where('module_type', ModuleType::VS_QR_SCAN_OUT->value)
                    //                                         ->first();

                    //     $data['vs_qr_scan_out'] = $activation_module->is_active;

                    //     return $data;
                    // })
                    // ->using(function (Model $record, array $data): Model {
                    //     $activationModule = ActivationModule::where('residence_id', $record->residence_id)
                    //         ->where('module_type', ModuleType::VS_QR_SCAN_OUT->value)
                    //         ->first();

                    //     $activationModule->update(['is_active' => $data['vs_qr_scan_out']]);

                    //     $record->update($data);

                    //     return $record;
                    // }),
                ]),
            ], position: RecordActionsPosition::BeforeColumns)
            ->toolbarActions([
                BulkActionGroup::make([
                    // DeleteBulkAction::make(),
                ]),
            ]);
    }
}
