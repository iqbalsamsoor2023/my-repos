<?php

namespace App\Filament\Resources\Units\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use function Sentry\captureException;
use App\Enums\GeneralStatus;
use App\Enums\UnitUser\ApprovalStatusType;
use App\Enums\UserFamily\Relationship;
use App\Filament\Resources\UnitUsers\UnitUserResource;
use App\Mail\UserRegistered as MailUserRegistered;
use App\Models\UnitUser;
use App\Models\User;
use App\Services\FilamentExport\FilamentExportBulkAction;
use Exception;
use Filament\Actions\ActionGroup;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;

class TenantsRelationManager extends RelationManager
{
    protected static string $relationship = 'tenants';

    protected static ?string $label = 'Tenant';

    protected static ?string $pluralLabel = 'Tenants';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $inverseRelationship = 'units';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('menu.tenants');
    }

    public static function getModelLabel(): ?string
    {
        return __('Tenant');
    }

    public static function getPluralModelLabel(): string
    {
        return __('menu.tenants');
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
                IconColumn::make('is_main_tenant')
                    ->label(__('user.is_main_tenant'))
                    ->boolean()
                    ->toggleable(),
                TextColumn::make('relationship')
                    ->label(__('user.relationship'))
                    ->formatStateUsing(function (string $state): string {
                        $relationships = [
                            Relationship::HUSBAND->value => __('user.'.strtolower(Relationship::HUSBAND->name)),
                            Relationship::WIFE->value => __('user.'.strtolower(Relationship::WIFE->name)),
                            Relationship::FATHER->value => __('user.'.strtolower(Relationship::FATHER->name)),
                            Relationship::MOTHER->value => __('user.'.strtolower(Relationship::MOTHER->name)),
                            Relationship::BROTHER->value => __('user.'.strtolower(Relationship::BROTHER->name)),
                            Relationship::SISTER->value => __('user.'.strtolower(Relationship::SISTER->name)),
                            Relationship::SON->value => __('user.'.strtolower(Relationship::SON->name)),
                            Relationship::DAUGHTER->value => __('user.'.strtolower(Relationship::DAUGHTER->name)),
                            Relationship::RELATIVE->value => __('user.'.strtolower(Relationship::RELATIVE->name)),
                            Relationship::CO_HOME->value => __('user.'.strtolower(Relationship::CO_HOME->name)),
                        ];

                        $relationship = $relationships[$state] ?? '-';
                        $relationship = __($relationship);

                        return $relationship;
                    })
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
                TernaryFilter::make('is_main_tenant')
                    ->label(__('user.is_main_tenant')),
                Filter::make('created_at')
                    ->label(__('app.created_at'))
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
            ])
            ->filtersFormColumns(3)
            ->headerActions([
                CreateAction::make()
                    ->label(__('menu.new_tenant'))
                    ->using(function (Component $livewire, array $data): Model {
                        $data['approval_status'] = ApprovalStatusType::APPROVED->value;
                        $user = User::findOrFail($data['user_id']);

                        if ($user->hasRole('Unit Tenant') == false) {
                            $user->assignRole('Unit Tenant');
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
                        ->mutateRecordDataUsing(function (array $data, Action $action, UnitUser $record = null): array {
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
