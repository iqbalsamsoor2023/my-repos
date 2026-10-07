<?php

namespace App\Filament\Resources\UserTutorials\Schemas;

use App\Enums\ResourceMaterial\ResourceTypeEnum;
use App\Filament\Resources\UserTutorials\Pages\CreateUserTutorial;
use App\Forms\Components\UserTutorial\UserTutorialVideo;
use App\Models\Erp\Platform;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Livewire\Component;

class UserTutorialForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('user.user_tutorial'))
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Select::make('platform_id')
                            ->label(__('app.platform'))
                            ->options(Platform::all()->pluck('name', 'id'))
                            ->required()
                            ->reactive(),
                        Hidden::make('type')
                            ->default(ResourceTypeEnum::USER_TUTORIAL->value),
                        TextInput::make('title')
                            ->label(__('app.title'))
                            ->required(),
                        TextInput::make('source_link')
                            ->label(__('Youtube Link'))
                            ->required(),
                        UserTutorialVideo::make('video')
                            ->label(__('app.video'))
                            ->dehydrated(false)
                            ->hidden(fn (Component $livewire): bool => $livewire instanceof CreateUserTutorial),
                    ]),
            ]);
    }
}
