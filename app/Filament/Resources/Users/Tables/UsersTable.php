<?php

namespace App\Filament\Resources\Users\Tables;

use App\Actions\Audit\CreateAuditAction;
use App\Enums\GeneralStatus;
use App\Enums\UserFamily\Relationship;
use App\Exports\UserExport;
use App\Filament\Resources\Units\RelationManagers\OwnersRelationManager;
use App\Filament\Resources\Units\RelationManagers\TenantsRelationManager;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Jobs\SendEmailUnitOwnerVerification;
use App\Models\UnitUser;
use App\Models\User;
use App\Services\FilamentExport\FilamentExportBulkAction;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DetachAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\ColumnGroup;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('mmb_id')
                    ->visible(fn(Component $livewire): bool => $livewire instanceof OwnersRelationManager || $livewire instanceof TenantsRelationManager),
                TextColumn::make('name')
                    ->label(__('app.name'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('email')
                    ->label(__('app.email'))
                    ->copyable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('phone_no')
                    ->label(__('app.phone_number'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('roles.name')
                    ->label(__('user.roles'))
                    ->toggleable()
                    ->hidden(fn(Component $livewire): bool => $livewire instanceof TenantsRelationManager || $livewire instanceof OwnersRelationManager),
                TextColumn::make('mode')
                    ->translateLabel()
                    ->getStateUsing(function (User $record) {
                        $unitUser = UnitUser::where('user_id', $record->id)->first();

                        $mode = ($unitUser && $unitUser->unit?->residence?->is_active === GeneralStatus::ACTIVE->value)
                            ? 'Live'
                            : ($unitUser ? 'Demo' : '');

                        return $mode;
                    })
                    ->toggleable()
                    ->visible(fn(Component $livewire): bool => $livewire instanceof TenantsRelationManager || $livewire instanceof OwnersRelationManager),
                IconColumn::make('email_verified_at')
                    ->label(__('app.is_verified'))
                    ->toggleable()
                    ->boolean(),
                TextColumn::make('pdpa_consent_status')
                    ->label(__('user.pdpa_consent_status'))
                    ->getStateUsing(function (User $record) {
                        return $record->pdpa_agreed_at ? 'Agreed' : 'Pending';
                    })
                    ->toggleable(),
                TextColumn::make('pdpa_agreed_at')
                    ->label(__('user.agreed_at'))
                    ->getStateUsing(function (User $record) {
                        if ($record->pdpa_agreed_at) {
                            return Carbon::parse($record->pdpa_agreed_at)->format('d-m-Y H:i:s');
                        }
                    })
                    ->toggleable(),
                IconColumn::make('is_main_owner')
                    ->label(__('app.is_main_owner'))
                    ->boolean()
                    ->toggleable()
                    ->visible(fn(Component $livewire): bool => $livewire instanceof OwnersRelationManager),
                IconColumn::make('is_main_tenant')
                    ->label(__('app.is_main_tenant'))
                    ->boolean()
                    ->toggleable()
                    ->visible(fn(Component $livewire): bool => $livewire instanceof TenantsRelationManager),
                TextColumn::make('relationship')
                    ->label(__('app.relationship'))
                    ->formatStateUsing(function (string $state): string {
                        $relationship = Relationship::tryFrom((int)$state);

                        return $relationship?->label() ?? '-';
                    })
                    ->toggleable()
                    ->visible(fn(Component $livewire): bool => $livewire instanceof TenantsRelationManager || $livewire instanceof OwnersRelationManager),
                ColumnGroup::make(__('app.created_at'), [
                    TextColumn::make('created_at_date')
                        ->label(__('app.date'))
                        ->getStateUsing(function (User $record) {
                            return $record->created_at->format('d-M-y');
                        }),
                    TextColumn::make('created_at_time')
                        ->label(__('app.time'))
                        ->getStateUsing(function (User $record) {
                            return $record->created_at->format('H:i:s');
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Filter::make('name')
                    ->schema([
                        TextInput::make('name')
                            ->label(__('app.name')),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (isset($data['name'])) {
                            return $query->where('name', 'LIKE', '%' . $data['name'] . '%');
                        }
                    }),
                Filter::make('email')
                    ->schema([
                        TextInput::make('email')
                            ->label(__('app.email')),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (isset($data['email'])) {
                            return $query->where('email', 'LIKE', '%' . $data['email'] . '%');
                        }
                    }),
                SelectFilter::make('roles')
                    ->label(__('user.roles'))
                    ->multiple()
                    ->relationship('roles', 'name'),
                SelectFilter::make('is_verified')
                    ->label(__('app.is_verified'))
                    ->options([
                        1 => __('Yes'),
                        2 => __('No'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['value'],
                                fn(Builder $query): Builder => $query->where(function ($q) use ($data) {
                                    if ($data['value'] == 1) {
                                        return $q->whereNotNull('email_verified_at');
                                    } elseif ($data['value'] == 2) {
                                        return $q->whereNull('email_verified_at');
                                    }
                                }),
                            );
                    }),
                SelectFilter::make('pdpa_agreed_at')
                    ->label(__('PDPA Consent'))
                    ->options([
                        1 => 'Agreed',
                        2 => 'Pending',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['value'],
                                fn(Builder $query): Builder => $query->where(function ($q) use ($data) {
                                    if ($data['value'] == 1) {
                                        return $q->whereNotNull('pdpa_agreed_at');
                                    } elseif ($data['value'] == 2) {
                                        return $q->whereNull('pdpa_agreed_at');
                                    }
                                }),
                            );
                    }),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('created_from')
                            ->label(__('app.created_from')),
                        DatePicker::make('created_until')
                            ->label(__('app.created_until')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(3)
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->hidden(function (User $record) {
                            $currentUser = auth()->user();

                            return $currentUser->hasRole('Admin') && $record->hasRole('Super Admin');
                        }),
                    ViewAction::make(),
                    DetachAction::make('detach')
                        ->icon('heroicon-o-x-mark')
                        ->visible(fn(Component $livewire): bool => $livewire instanceof TenantsRelationManager || $livewire instanceof OwnersRelationManager)
                        ->action(fn(DetachAction $action) => $action->getRecord()->pivot->delete()),
                    DeleteAction::make()
                        ->hidden(fn(Component $livewire): bool => $livewire instanceof TenantsRelationManager || $livewire instanceof OwnersRelationManager),
                    Action::make('email verify')
                        ->icon('heroicon-o-envelope')
                        ->tooltip(fn(User $record): string => "Resend verification mail at {$record->email}")
                        ->hidden(fn(Component $livewire): bool => $livewire instanceof ListUsers)
                        ->visible(fn(User $record): string => is_null($record->email_verified_at) == true)
                        ->action(fn(User $record) => dispatch(new SendEmailUnitOwnerVerification(explode(',', $record->email), $record))),
                ]),
            ], position: RecordActionsPosition::BeforeColumns)
            ->toolbarActions([
                FilamentExportBulkAction::make('export')
                    ->fileName('User-Report')
                    ->disableAdditionalColumns()
                    ->disableCsv()
                    ->disablePdf()
                    ->disableFilterColumns()
                    ->action(function (Component $livewire) {
                        $columns = collect($livewire->getTable()->getColumns())
                                        ->filter(fn ($col) => $col->isVisible())
                                        ->map(fn ($col) => $col->getName())
                                        ->values()
                                        ->toArray(); 
                        $fileName = $livewire->mountedActions[0]['data']['file_name'] . '.xlsx';

                        $ids = $livewire->getSelectedTableRecords()->pluck('id')->toArray();
                        $users = User::whereIn('id', $ids)->latest('id')->get();

                        $audit = new CreateAuditAction();
                        $audit->execute(new Request([
                            'user_type' => get_class(auth()->user()),
                            'user_id' => auth()->id(),
                            'event' => 'exported',
                            'old_values' => [],
                            'new_values' => ['action' => 'exported user records'],
                            'url' => request()->fullUrl(),
                            'ip_address' => request()->ip(),
                            'user_agent' => request()->userAgent(),
                        ]));

                        return Excel::download(new UserExport($users, $columns), $fileName);
                    }),
            ]);
    }
}
