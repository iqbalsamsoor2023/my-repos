<?php

namespace App\Filament\Resources\Residences\RelationManagers;

use App\Enums\DigitalTool\DtaDigitalToolStatusEnum;
use App\Helpers\Sgoc\GuardTalkAccountGenerator;
use App\Http\Integrations\MySgoc\SecurityGuard\Requests\CreateSecurityGuardRequest;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class GuardTalkAccountsRelationManager extends RelationManager
{
    protected static string $relationship = 'guardTalkAccounts';

    protected static ?string $recordTitleAttribute = 'user_id';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('status')
                    ->label(__('app.status'))
                    ->badge()
                    ->formatStateUsing(function ($state) {
                        $status = $state instanceof DtaDigitalToolStatusEnum
                            ? $state
                            : DtaDigitalToolStatusEnum::tryFrom($state);

                        return $status?->label();
                    })
                    ->color(function ($state) {
                        $status = $state instanceof DtaDigitalToolStatusEnum
                            ? $state
                            : DtaDigitalToolStatusEnum::tryFrom($state);

                        return $status?->color();
                    }),
                TextColumn::make('digitalTool.asset_code')
                    ->label(__('app.digital_device')),
                TextColumn::make('sim.number')
                    ->label(__('app.sim_number')),
                TextColumn::make('email')
                    ->label(__('app.email'))
                    ->copyable()
                    ->badge()
                    ->getStateUsing(function ($record) {
                        return $record->user_platform == 'sgoc' && $record->sgocUser ? $record->sgocUser->email : null;
                    }),
                IconColumn::make('service_end_date')
                    ->label(__('app.is_active'))
                    ->boolean()
                    ->toggleable()
                    ->getStateUsing(function ($record) {
                        return $record->service_end_date == null;
                    })
                    ->trueIcon('heroicon-o-check')
                    ->falseIcon('heroicon-o-x-mark'),
            ])
            ->recordActions([
                Action::make('create_gt_account')
                    ->label(__('residence.create_gt_account'))
                    ->visible(function (Model $record) {
                        $hasNoUser = ! $record->sgocUser;
                        $isServiceActive = is_null($record->service_end_date) || $record->service_end_date > now()->toDateString();

                        return $hasNoUser && $isServiceActive;
                    })
                    ->action(function (Model $record) {
                        try {
                            $residence = $record->digitalToolAllocation->residence;
                            $guardTalkAccountDetails = GuardTalkAccountGenerator::execute($residence);

                            $request = new CreateSecurityGuardRequest($guardTalkAccountDetails);
                            $response = $request->send();

                            // Check for HTTP success
                            if (! $response->successful()) {
                                throw new \Exception("Failed to create GT account. HTTP Status: {$response->status()}");
                            }

                            // Get user ID from response
                            $userId = $response->json('data.id');

                            if (! $userId) {
                                throw new \Exception('SGOC API responded successfully but no user ID was returned.');
                            }

                            // Update DtaDigitalTool record
                            $record->update([
                                'user_platform' => 'sgoc',
                                'user_id' => $userId,
                            ]);

                            Notification::make()
                                ->title(__('residence.gt_account_created'))
                                ->body("Successfully created: {$guardTalkAccountDetails['email']}")
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title(__('residence.gt_account_creation_failed'))
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->icon('heroicon-o-plus'),
                Action::make('change_status')
                    ->label(__('menu.change_status'))
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->schema([
                        Select::make('status')
                            ->label(__('app.status'))
                            ->options([
                                DtaDigitalToolStatusEnum::ACTIVE->value => DtaDigitalToolStatusEnum::ACTIVE->label(),
                                DtaDigitalToolStatusEnum::SUSPENDED->value => DtaDigitalToolStatusEnum::SUSPENDED->label(),
                            ])
                            ->default(function (Model $record) {
                                return $record->status instanceof DtaDigitalToolStatusEnum
                                    ? $record->status->value
                                    : $record->status;
                            })
                            ->required(),
                    ])
                    ->action(function (Model $record, array $data) {
                        $record->update([
                            'status' => $data['status'],
                        ]);

                        Notification::make()
                            ->title(__('status.status_updated'))
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalDescription(__('app.are_you_sure_you_would_like_to_do_this'))
                    ->visible(function (Model $record) {
                        $status = $record->status instanceof DtaDigitalToolStatusEnum
                            ? $record->status
                            : DtaDigitalToolStatusEnum::tryFrom($record->status);

                        return ! in_array($status, [
                            DtaDigitalToolStatusEnum::CANCELLED,
                            DtaDigitalToolStatusEnum::EXPIRED,
                        ]);
                    }),
            ]);
    }
}
