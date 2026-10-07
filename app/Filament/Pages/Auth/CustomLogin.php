<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

class CustomLogin extends BaseLogin
{
    protected string $view = 'filament.pages.auth.custom-login';

    public function getTitle(): string|Htmlable
    {
        return 'Sign In to MyMooban';
    }

    public function getHeading(): string|Htmlable
    {
        return '';
    }

    public function hasLogo(): bool
    {
        return false;
    }

    public function getLayout(): string
    {
        return 'filament.layouts.auth';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required()
                    ->autocomplete()
                    ->autofocus()
                    ->extraAttributes(['class' => 'custom-email-input'])
                    ->extraInputAttributes([
                        'aria-hidden' => 'true',
                        'autocomplete' => 'off',
                        'data-lpignore' => 'true',
                        'disabled' => true,
                        'placeholder' => 'your@email.com',
                        'style' => 'padding-left: 2.5rem !important;',
                        'tabindex' => '-1',
                    ]),

                TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->required()
                    ->autocomplete('current-password')
                    ->extraAttributes(['class' => 'custom-password-input'])
                    ->extraInputAttributes([
                        'aria-hidden' => 'true',
                        'autocomplete' => 'off',
                        'data-lpignore' => 'true',
                        'disabled' => true,
                        'id' => 'password-field',
                        'placeholder' => '********',
                        'style' => 'padding-left: 2.5rem !important; padding-right: 3rem !important;',
                        'tabindex' => '-1',
                    ]),

                Checkbox::make('remember')
                    ->label('Remember me')
                    ->extraInputAttributes([
                        'aria-hidden' => 'true',
                        'disabled' => true,
                        'tabindex' => '-1',
                    ]),
            ])
            ->statePath('data');
    }
}
