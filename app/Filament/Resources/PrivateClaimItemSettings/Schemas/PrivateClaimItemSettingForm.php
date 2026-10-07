<?php

namespace App\Filament\Resources\PrivateClaimItemSettings\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class PrivateClaimItemSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('app.residence'))
                    ->description(__('app.residence_detail'))
                    ->columns(2)
                    ->schema([
                        Select::make('residence_id')
                            ->relationship('residence', 'name')
                            ->label(__('residence.mooban'))
                            ->searchable()
                            ->required()
                            ->visible(fn () => auth()->user()?->hasRole('Super Admin')),
                            Select::make('private_claim_item_id')
                            ->relationship('privateClaimItem', 'name')
                            ->getOptionLabelFromRecordUsing(function ($record) {
                                return app()->getLocale() === 'th' && $record->name_th
                                    ? $record->name_th
                                    : $record->name;
                            })
                            ->label(__('maintenance.private_claim_item'))
                            ->required(),
                        Toggle::make('has_warranty')
                            ->label(__('maintenance.has_warranty'))
                            ->reactive()
                            ->inline(false),
                        TextInput::make('warranty_period')
                            ->label(__('maintenance.warranty_period'))
                            ->numeric()
                            ->minValue(1)
                            ->required()
                            ->visible(fn (Get $get) => $get('has_warranty') == true),
                            Select::make('period_type')
                            ->label(__('maintenance.period_type'))
                            ->options([
                                'month' => __('maintenance.months'),
                                'year' => __('maintenance.years'),
                            ])
                            ->required()
                            ->visible(fn (Get $get) => $get('has_warranty') == true),
                        // TextInput::make('supplier')
                        //     ->label(__('maintenance.supplier'))
                        //     ->required()
                        //     ->maxLength(255)
                        //     ->visible(fn (Get $get) => $get('has_warranty') == true),
                        Toggle::make('is_out_warranty')
                            ->label(__('maintenance.action_if_out_of_warranty'))
                            ->reactive()
                            ->inline(false)
                            ->visible(fn (Get $get) => $get('has_warranty') == true),
                        Textarea::make('remark')
                            ->label(__('app.remark'))
                            ->required()
                            ->reactive()
                            ->maxLength(255)
                            ->visible(fn (Get $get) => $get('is_out_warranty') == true),
                    ]),

                Section::make(__('maintenance.supplier'))
                    ->description(__('maintenance.supplier_details'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('supplier_company_name')
                            ->label(__('maintenance.supplier_company_name'))
                            ->maxLength(255),
                        TextInput::make('supplier_company_name_th')
                            ->label(__('maintenance.supplier_company_name_th'))
                            ->maxLength(255),
                        TextInput::make('supplier_item_brand')
                            ->label(__('maintenance.supplier_item_brand'))
                            ->maxLength(255),
                        TextInput::make('pic_name')
                            ->label(__('app.name'))
                            ->maxLength(255),
                        TextInput::make('pic_mobile_no')
                            ->label(__('app.contact_no'))
                            ->maxLength(255),
                        TextInput::make('pic_email')
                            ->label(__('app.email'))
                            ->email()
                            ->maxLength(255),
                    ])
            ])->columns(1);
    }
}
