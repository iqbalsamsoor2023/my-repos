<?php

namespace App\Filament\Resources\EventRsvps\Schemas;

use App\Models\EventRsvp;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Livewire\Component;

class EventRsvpForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('app.rsvp'))
                    ->description(__('app.rsvp_detail'))
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Select::make('user_id')
                            ->label(__('app.resident'))
                            ->required()
                            ->options(function (Component $livewire) {
                                if ($livewire->mountedActions[0]['name'] == 'create') {
                                    $user_ids = EventRsvp::where('event_id', $livewire->ownerRecord->id)->pluck('user_id')->toArray();

                                    return User::role('Unit Owner')->whereNotIn('id', $user_ids)
                                        ->whereHas('unitUsers.unit', function ($query) use ($livewire) {
                                            $query->where('residence_id', $livewire->ownerRecord->residence_id);
                                        })->pluck('name', 'id');
                                } elseif ($livewire->mountedActions[0]['name'] == 'edit') {
                                    return User::role('Unit Owner')->whereId($livewire->mountedActions[0]['data']['user_id'])->pluck('name', 'id');
                                }
                            })
                            ->searchable()
                            ->disabled(function (Component $livewire) {
                                return $livewire->mountedActions[0]['name'] == 'edit';
                            }),
                        Toggle::make('is_going')
                            ->label(__('app.is_going'))
                            ->inline(false)
                            ->required(),
                    ]),
            ]);
    }
}
