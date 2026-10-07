<?php

namespace App\Filament\Resources\LogisticPartners\Schemas;

use App\Enums\LogisticPartner\ModesType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class LogisticPartnerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('app.companies_logo'))
                    ->columnSpanFull()
                    ->schema([
                        Group::make([
                            TextInput::make('name')
                                ->label(__('app.name'))
                                ->required()
                                ->unique(ignorable: fn(?Model $record): ?Model => $record)
                                ->maxLength(255),
                            Select::make('modes')
                                ->label(__('parcel.modes'))
                                ->options(ModesType::options())
                                ->searchable()
                                ->required(),
                        ])->columns(2),
                        SpatieMediaLibraryFileUpload::make('image')
                            ->label(__('app.image'))
                            ->disk('public')
                            ->image()
                            ->collection('default')
                            ->openable()
                            ->columnSpanFull(),
                    ])
            ]);
    }
}
