<?php

namespace App\Filament\Resources\SupportTickets\Tables;

use App\Enums\SupportTicket\SupportTicketStatusEnum;
use App\Models\Audit;
use App\Models\Erp\ChatCategory;
use App\Models\Residence;
use App\Models\SupportTicket;
use App\Models\Unit;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class SupportTicketsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('case_generated_no')
                    ->label(__('support-ticket.case_generated_no'))
                    ->copyable()
                    ->toggleable(),
                TextColumn::make('unit.residence.name')
                    ->label(__('app.mooban_or_residence'))
                    ->toggleable()
                    ->description(fn(SupportTicket $record): string => $record?->unit?->residence?->name_th ?? '-')
                    ->visible(auth()->user()->hasRole(['Super Admin', 'Admin', 'Property Management Operation Center'])),
                TextColumn::make('unit.unit_number')
                    ->label(__('unit.unit_number'))
                    ->toggleable(),
                ViewColumn::make('resident')
                    ->label(__('app.resident'))
                    ->view('tables.columns.support-ticket.resident-details')
                    ->toggleable(),
                TextColumn::make('chatCategoryItem.chatCategory.name')
                    ->label(__('support-ticket.category'))
                    ->copyable()
                    ->toggleable()
                    ->formatStateUsing(function ($record) {
                        return $record->chat_category_item_id ? $record->chatCategoryItem?->chatCategory?->name : 'Others';
                    })
                    ->getStateUsing(function ($record) {
                        return $record->chatCategoryItem ? $record->chatCategoryItem->chatCategory->name : 'Others';
                    })
                    ->description(function ($record) {
                        return $record->chat_category_item_id ? $record?->chatCategoryItem?->chatCategory?->name_in_thai : 'อื่นๆ';
                    }),
                TextColumn::make('chatCategoryItem.title')
                    ->label(__('app.title'))
                    ->copyable()
                    ->toggleable()
                    ->description(fn(SupportTicket $record): string => $record?->chatCategoryItem?->title_in_thai ?? '-'),
                TextColumn::make('content')
                    ->label(__('support-ticket.content'))
                    ->limit(50)
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(function ($state) {
                        $status = SupportTicketStatusEnum::tryFrom($state);

                        return $status?->label() ?? '-';
                    })
                    ->color(function ($state) {
                        $status = SupportTicketStatusEnum::tryFrom($state);

                        return $status?->color() ?? 'secondary';
                    }),
                TextColumn::make('comments_count')
                    ->label(__('support-ticket.total_comments'))
                    ->counts('comments')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('app.created_at_date'))
                    ->getStateUsing(function (SupportTicket $record) {
                        return $record->created_at->format('d-M-y');
                    }),
                TextColumn::make('created_at_time')
                    ->label(__('app.created_at_time'))
                    ->getStateUsing(function (SupportTicket $record) {
                        return $record->created_at->format('H:i:s');
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('residence')
                    ->label(__('app.mooban_or_residence'))
                    ->options(list_residences())
                    ->searchable()
                    ->query(function (Builder $query, array $data): Builder {
                        if (isset($data['value'])) {
                            $units = Unit::where('residence_id', $data['value'])->pluck('id');

                            return $query
                                ->when(
                                    $data['value'],
                                    fn(Builder $query): Builder => $query->whereIn('unit_id', $units->toArray()),
                                );
                        }

                        return $query;
                    })
                    ->visible(auth()->user()->hasRole(['Super Admin', 'Property Management Operation Center'])),
                Filter::make('unit')
                    ->schema([
                        TextInput::make('unit_number')
                            ->label(__('unit.unit_number')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (is_null($data['unit_number']) == false) {
                            $units = Unit::where('unit_number', 'LIKE', '%' . $data['unit_number'] . '%')->get();

                            return $query
                                ->when(
                                    $data['unit_number'],
                                    fn(Builder $query): Builder => $query->whereIn('unit_id', $units->pluck('id')->toArray()),
                                );
                        }

                        return $query;
                    }),
                Filter::make('resident')
                    ->schema([
                        TextInput::make('resident')
                            ->label(__('app.resident')),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (isset($data['resident'])) {
                            $users = User::where('name', 'LIKE', '%' . $data['resident'] . '%')->pluck('id');

                            return $query
                                ->when(
                                    $data['resident'],
                                    fn(Builder $query): Builder => $query->whereIn('assigned_to', $users->toArray()),
                                );
                        }

                        return $query;
                    }),
                Filter::make('case_generated_no')
                    ->schema([
                        TextInput::make('case_generated_no')
                            ->label(__('support-ticket.case_generated_no')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['case_generated_no'],
                                fn(Builder $query): Builder => $query->where('case_generated_no', $data['case_generated_no']),
                            );
                    }),
                Filter::make('category')
                    ->schema([
                        Select::make('category')
                            ->label(__('support-ticket.category'))
                            ->options(ChatCategory::all()->pluck('name', 'id'))
                            ->searchable(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when($data['category'], function (Builder $query, $categoryId) {
                            if ($categoryId === '5') {
                                // Only show support tickets where the category item is NULL (Others)
                                return $query->whereNull('chat_category_item_id');
                            }

                            // Otherwise, show support tickets where the related item's category matches
                            return $query->whereIn('chat_category_item_id', function ($subQuery) use ($categoryId) {
                                $subQuery->select('id')
                                    ->from('chat_category_items')
                                    ->where('chat_category_id', $categoryId);
                            });
                        });
                    }),
                Filter::make('status')
                    ->schema([
                        Select::make('status')
                            ->label(__('app.status'))
                            ->options(SupportTicketStatusEnum::options())
                            ->searchable(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['status'],
                                fn(Builder $query): Builder => $query->where('status', $data['status']),
                            );
                    }),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(2)
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    Action::make('comment')
                        ->label(__('Comment'))
                        ->icon('heroicon-o-chat-bubble-oval-left-ellipsis')
                        ->url(fn(SupportTicket $record): string => route('filament.admin.resources.support-tickets.comment', $record->id))
                        ->visible(function (Component $livewire, SupportTicket $supportTicket) {
                            if (auth()->user()->hasAnyRole('Admin')) {
                                $demoResidenceIds = Residence::whereIn('residence_activation_status_id', [1, 6])->pluck('id');

                                return $demoResidenceIds->contains($supportTicket?->unit?->residence_id);
                            }

                            return true;
                        }),
                    ViewAction::make(),
                    DeleteAction::make()
                        ->before(function ($record) {
                            $oldValues = $record->only([
                                'id',
                                'case_generated_no',
                                'unit_id',
                                'content',
                                'platform_identifier',
                                'category',
                                'status',
                                'submitted_by',
                                'assigned_to',
                            ]);

                            $oldValues['created_at'] = optional($record->created_at)->format('Y-m-d H:i:s');
                            $oldValues['updated_at'] = optional($record->updated_at)->format('Y-m-d H:i:s');

                            Audit::create([
                                'user_type' => get_class(auth()->user()),
                                'user_id' => auth()->id(),
                                'event' => 'deleted',
                                'auditable_type' => get_class($record),
                                'auditable_id' => $record->id,
                                'old_values' => $oldValues,
                                'new_values' => null,
                                'url' => request()->fullUrl(),
                                'ip_address' => request()->ip(),
                                'user_agent' => request()->userAgent(),
                            ]);
                        }),
                ]),
            ], position: RecordActionsPosition::BeforeColumns)
            ->toolbarActions([
                DeleteBulkAction::make()
                    ->hidden(auth()->user()->hasAnyRole(['Admin'])),
            ]);
    }
}
