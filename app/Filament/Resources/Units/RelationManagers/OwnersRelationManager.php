<?php

namespace App\Filament\Resources\Units\RelationManagers;

use App\Enums\GeneralStatus;
use App\Enums\UnitUser\ApprovalStatusType;
use App\Filament\Resources\UnitUsers\UnitUserResource;
use App\Mail\UserRegistered as MailUserRegistered;
use App\Models\UnitUser;
use App\Models\User;
use App\Services\FilamentExport\FilamentExportBulkAction;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;

use function Sentry\captureException;

class OwnersRelationManager extends RelationManager
{
    protected static string $relationship = 'owners';

    protected static ?string $label = 'Owner';

    protected static ?string $pluralLabel = 'Owners';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('menu.owners');
    }

    public static function getModelLabel(): ?string
    {
        return __('Owner');
    }

    public static function getPluralModelLabel(): string
    {
        return __('menu.owners');
    }

    public function form(Schema $schema): Schema
    {
        return UnitUserResource::form($schema);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('mmb_id')
                    ->label(__('app.mmb_id'))
                    ->toggleable(),
                TextColumn::make('user.name')
                    ->label(__('app.name'))
                    ->toggleable(),
                TextColumn::make('user.email')
                    ->label(__('app.email'))
                    ->toggleable(),
                TextColumn::make('user.phone_no')
                    ->label(__('app.phone_number'))
                    ->toggleable(),
                IconColumn::make('user.email_verified_at')
                    ->label(__('app.is_verified'))
                    ->boolean()
                    ->toggleable(),
                IconColumn::make('is_main_owner')
                    ->label(__('user.is_main_owner'))
                    ->boolean()
                    ->toggleable(),
                TextColumn::make('relationship')
                    ->label(__('user.relationship'))
                    ->toggleable(),
                TextColumn::make('approval_status')
                    ->label(__('user.approval_status'))
                    ->formatStateUsing(function (string $state): string {
                        $approvalStatuses = [
                            ApprovalStatusType::APPROVED->value => __('app.'.strtolower(ApprovalStatusType::APPROVED->name)),
                            ApprovalStatusType::REJECTED->value => __('app.'.strtolower(ApprovalStatusType::REJECTED->name)),
                        ];

                        $approvalStatus = $approvalStatuses[$state] ?? '-';

                        return __($approvalStatus);
                    })
                    ->toggleable(),
                TextColumn::make('mail_status')
                    ->label(__('user.email_status'))
                    ->formatStateUsing(function (string $state): string {
                        return $state == GeneralStatus::INACTIVE->value ? __('Pending') : __('Verified');
                    })
                    ->getStateUsing(fn (Model $record) => ($record->user->email_verified_at ?? 0) ? 1 : 0)
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('app.created_at_date'))
                    ->date(),
                TextColumn::make('created_at_time')
                    ->label(__('app.created_at_time'))
                    ->getStateUsing(function (UnitUser $record) {
                        return $record->created_at->format('H:i');
                    }),
            ])
            ->filters([
                TernaryFilter::make('is_owner')
                    ->label(__('user.is_owner')),
                TernaryFilter::make('is_main_owner')
                    ->label(__('user.is_main_owner')),
                Filter::make('created_at')
                    ->label(__('app.created_at'))
                    ->schema([
                        DatePicker::make('created_from')->label(__('app.created_from'))->default(null),
                        DatePicker::make('created_until')->label(__('app.created_until'))->default(null),
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
            ])
            ->filtersFormColumns(3)
            ->headerActions([
                CreateAction::make()
                    ->label(__('menu.new_owner'))
                    ->using(function (Component $livewire, array $data): Model {
                        $data['approval_status'] = ApprovalStatusType::APPROVED->value;
                        $data['is_owner'] = true;
                        $user = User::findOrFail($data['user_id']);
                        if ($user->hasRole('Unit Owner') == false) {
                            $user->assignRole('Unit Owner');
                        }

                        if (empty($user->email_verified_at)) {
                            if ($user->email) {
                                try {
                                    Mail::to($user->email)->send(new MailUserRegistered($user));
                                } catch (Exception $ex) {
                                    captureException($ex);
                                }
                            }
                        }

                        return $livewire->getRelationship()->create($data);
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->mutateRecordDataUsing(function (array $data, Action $action, ?UnitUser $record = null): array {
                            $data['email_status'] = ($record->user->email_verified_at ?? null) ? GeneralStatus::ACTIVE->value : GeneralStatus::INACTIVE->value;
                            if (isset($data['relationship']) && ! is_numeric($data['relationship'])) {
                                $data['relationship'] = $action->getRecord()->getAttributes()['relationship'];
                            }

                            return $data;
                        })
                        ->using(function (Model $record, array $data): Model {
                            $emailStatus = $data['email_status'] ?? GeneralStatus::INACTIVE->value;
                            unset($data['email_status']);
                            $record->update($data);

                            $record->user->fill([
                                'email_verified_at' => $emailStatus == GeneralStatus::ACTIVE->value ? $record->freshTimestamp() : null,
                            ])->save();

                            return $record;
                        }),
                    DeleteAction::make(),
                ]),
            ], position: RecordActionsPosition::BeforeColumns)
            ->toolbarActions([
                DeleteBulkAction::make(),
                FilamentExportBulkAction::make('export')
                    ->fileName('Resident-Report')
                    ->disableAdditionalColumns()
                    ->disableCsv()
                    ->disablePdf(),
            ]);
    }
}
