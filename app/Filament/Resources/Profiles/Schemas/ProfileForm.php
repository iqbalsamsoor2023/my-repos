<?php

namespace App\Filament\Resources\Profiles\Schemas;

use App\Filament\Resources\Profiles\Pages\EditProfile;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class ProfileForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('')
                    ->description(__('user.my_profile'))
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label(__('app.name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label(__('app.email'))
                            ->email()
                            ->unique(ignorable: fn(?Model $record): ?Model => $record)
                            ->required()
                            ->placeholder('user@mymooban.co.th')
                            ->maxLength(255)
                            ->disabled(true),
                        TextInput::make('password')
                            ->label(__('user.password'))
                            ->autocomplete(false)
                            ->password()
                            ->same('confirm_password')
                            ->minLength(8)
                            ->maxLength(255)
                            ->dehydrated(fn($state): string => is_null($state) ? false : true)
                            ->dehydrateStateUsing(fn($state): string => Hash::make($state))
                            ->reactive()
                            ->visible(fn(Component $livewire): bool => $livewire instanceof EditProfile),
                        TextInput::make('confirm_password')
                            ->label(__('user.confirm_password'))
                            ->password()
                            ->minLength(8)
                            ->dehydrated(false)
                            ->hidden(fn(Get $get) => $get('password') == null),
                        SpatieMediaLibraryFileUpload::make('profile')
                            ->label(__('user.profile'))
                            ->disk('cos')
                            ->image()
                            ->openable(true),
                    ])
            ]);
    }
}
