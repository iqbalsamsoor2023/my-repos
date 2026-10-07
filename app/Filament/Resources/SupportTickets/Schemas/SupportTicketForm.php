<?php

namespace App\Filament\Resources\SupportTickets\Schemas;

use App\Enums\SupportTicket\SupportTicketStatusEnum;
use App\Enums\User\RoleType;
use App\Filament\Resources\SupportTickets\Pages\CreateSupportTicket;
use App\Filament\Resources\SupportTickets\Pages\EditSupportTicket;
use App\Models\Erp\ChatCategory;
use App\Models\Erp\ChatCategoryItem;
use App\Models\Residence;
use App\Models\Unit;
use App\Models\User;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\App;
use Livewire\Component;

class SupportTicketForm
{
    public static function configure(Schema $schema): Schema
    {
        $user = auth()->user();

        return $schema
            ->components([
                Section::make(__('support-ticket.support_ticket'))
                    ->description(__('support-ticket.support_ticket_detail'))
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Hidden::make('platform_identifier')
                            ->default('mmb'),
                        Hidden::make('submitted_by')
                            ->default($user->id),
                        Select::make('residence_id')
                            ->label(__('app.mooban_or_residence'))
                            ->options(function (Component $livewire) {
                                if ($livewire instanceof CreateSupportTicket) {
                                    return list_create_residences();
                                }

                                return list_residences();
                            })
                            ->searchable()
                            ->required()
                            ->reactive()
                            ->disabled(fn(Component $livewire): bool => $livewire instanceof EditSupportTicket),
                        Select::make('unit_id')
                            ->label(__('unit.unit_number'))
                            ->options(function (callable $get) use ($user) {
                                $residence_id = $get('residence_id');

                                if ($user->hasRole('Property Management')) {
                                    $residence_id = Residence::where('property_management_user_id', $user->id)->pluck('id');
                                }

                                return Unit::where('residence_id', $residence_id)->pluck('unit_number', 'id');
                            })
                            ->reactive()
                            ->searchable()
                            ->required()
                            ->disabled(fn(Component $livewire): bool => $livewire instanceof EditSupportTicket),
                        Select::make('assigned_to')
                            ->label(__('user.receiver'))
                            ->options(function (callable $get) {
                                $users = [];
                                $users = User::whereHas('roles', function ($query) {
                                    $query->whereIn('name', [RoleType::UNIT_OWNER->value, RoleType::UNIT_TENANT->value]);
                                })->whereHas('units', function (Builder $builder) use ($get) {
                                    return $builder->where('unit_user.unit_id', $get('unit_id'));
                                })->pluck('name', 'id')->toArray();

                                return $users;
                            })
                            ->reactive()
                            ->required()
                            ->visible(fn(Component $livewire): bool => $livewire instanceof CreateSupportTicket),
                        Select::make('assigned_to')
                            ->label(__('user.receiver'))
                            ->options(function ($record) {
                                return User::whereId($record->assigned_to)->pluck('name', 'id')->toArray();
                            })
                            ->disabled()
                            ->visible(fn(Component $livewire): bool => $livewire instanceof EditSupportTicket),
                        Select::make('chat_category_id')
                            ->label(__('support-ticket.category'))
                            ->options(function () {
                                $isThai = App::getLocale() === 'th';

                                return ChatCategory::where('name', '!=', 'Others')
                                    ->get()
                                    ->pluck('id')
                                    ->mapWithKeys(function ($id) use ($isThai) {
                                        $category = ChatCategory::find($id);
                                        if (!$category) return [];

                                        $name = $category->name;
                                        $thaiName = $category->name_in_thai;

                                        $label = $isThai ? "{$thaiName}" : "{$name}";

                                        return [$id => $label];
                                    })
                                    ->toArray();
                            })
                            ->reactive()
                            ->searchable()
                            ->required()
                            ->disabled(fn($record) => $record?->exists),
                        Select::make('chat_category_item_id')
                            ->label(__('support-ticket.chat_category_item'))
                            ->options(function (callable $get) {
                                $isThai = App::getLocale() === 'th';

                                return ChatCategoryItem::select('id', 'title', 'title_in_thai')
                                    ->where('chat_category_id', $get('chat_category_id'))
                                    ->get()
                                    ->mapWithKeys(function ($item) use ($isThai) {
                                        $label = $isThai ? $item->title_in_thai : "{$item->title} ({$item->title_in_thai})";
                                        return [$item->id => $label];
                                    })
                                    ->toArray();
                            })
                            ->reactive()
                            ->searchable()
                            ->required()
                            ->hidden(function (callable $get) {
                                return $get('chat_category_id') == 5; // Hide if 'Others'
                            })
                            ->disabled(fn($record) => $record?->exists),
                        Textarea::make('content')
                            ->label(__('support-ticket.content'))
                            ->required()
                            ->disabled(fn($record) => $record?->exists),
                        Select::make('status')
                            ->label(__('app.status'))
                            ->required()
                            ->options(SupportTicketStatusEnum::options())
                            ->searchable()
                            ->hidden(
                                fn(Get $get, Component $livewire) =>
                                $get('residence_id') == null ||
                                    Residence::whereId($get('residence_id'))
                                    ->where('support_ticket_status', false)
                                    ->exists() ||
                                    $livewire instanceof CreateSupportTicket
                            )
                            ->dehydrated(),
                        SpatieMediaLibraryFileUpload::make('file')
                            ->label(__('app.file'))
                            ->collection('support_ticket_attachment')
                            ->customProperties(['type' => 'document'])
                            ->disk('cos')
                            ->multiple()
                            ->maxFiles(5)
                            ->openable(true)
                            ->downloadable(true)
                            ->disabled(fn($record) => $record?->exists),
                        //     ->visible(fn (Component $livewire): bool => $livewire instanceof Pages\CreateSupportTicket),
                        // SupportTicketFile::make('file')
                        //     ->label(__('app.file'))
                        //     ->translateLabel()
                        //     ->dehydrated(false)
                        //     ->visible(fn (Component $livewire): bool => $livewire instanceof Pages\EditSupportTicket),
                    ])
            ]);
    }
}
