<?php

namespace App\Filament\Resources\InsuranceCompanies\Schemas;

use App\Enums\Company\InsuranceTypeEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InsuranceCompanyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('company.insurance_company'))
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Select::make('type')
                            ->label(__('app.type'))
                            ->options(collect(InsuranceTypeEnum::cases())
                                ->mapWithKeys(fn($case) => [$case->value => __($case->label())])
                                ->toArray())
                            ->searchable()
                            ->reactive()
                            ->required(),
                        TextInput::make('name')
                            ->label(__('app.company_name_en'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('name_th')
                            ->label(__('app.company_name_th'))
                            ->required()
                            ->maxLength(255),
                        SpatieMediaLibraryFileUpload::make('logo')
                            ->translateLabel()
                            ->collection('insurance_company_images')
                            ->customProperties(['type' => 'Insurance'])
                            ->disk('cos')
                            ->openable(true),
                        TextInput::make('website_url')
                            ->label(__('app.website_url'))
                            ->url()
                            ->maxLength(255),
                        Toggle::make('is_active')
                            ->label(__('app.is_active'))
                            ->required(),
                    ])
            ]);
    }
}
