<?php

namespace App\Filament\Resources\PrivateClaimSuppliers\Schemas;

use App\Models\PrivateClaimCategory;
use App\Models\PrivateClaimItem;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PrivateClaimSupplierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Claim Supplier Setup')
                    ->schema([

                        // =============================
                        // 1️⃣ FIELDSET: CLAIM SELECTION
                        // =============================
                        Fieldset::make('Claim Item Title Information')
                            ->schema([
                                // Category
                                Select::make('private_claim_category_id')
                                    ->label('Category')
                                    ->options(PrivateClaimCategory::pluck('name', 'id'))
                                    ->reactive()
                                    ->afterStateUpdated(fn(callable $set) => [
                                        $set('private_claim_item_id', null),
                                        $set('private_claim_item_title_id', null),
                                    ])
                                    ->required(),

                                // Item (filtered by Category)
                                Select::make('private_claim_item_id')
                                    ->label('Claim Item')
                                    ->options(function (callable $get) {
                                        if (!$get('private_claim_category_id')) {
                                            return [];
                                        }

                                        return PrivateClaimItem::where(
                                            'private_claim_category_id',
                                            $get('private_claim_category_id')
                                        )->pluck('name', 'id');
                                    })
                                    ->reactive()
                                    ->afterStateUpdated(fn(callable $set) => $set('private_claim_item_title_id', null))
                                    ->required(),
                            ])
                            ->columns(2),

                        // ============================
                        // 2️⃣ FIELDSET: SUPPLIER DETAILS
                        // ============================
                        Fieldset::make('Supplier Details')
                            ->schema([
                                TextInput::make('company_name')
                                    ->label('Company Name')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('company_name_th')
                                    ->label('Company Name (TH)')
                                    ->maxLength(255),
                                TextInput::make('brand')
                                    ->maxLength(255),
                                TextInput::make('contact_person_name')
                                    ->label('Contact Person Name')
                                    ->maxLength(255),
                                TextInput::make('contact_person_mobile_no')
                                    ->label('Contact Person Mobile No')
                                    ->maxLength(255),
                                TextInput::make('contact_person_email')
                                    ->label('Contact Person Email')
                                    ->email()
                                    ->maxLength(255),
                            ])
                            ->columns(2),

                    ]),
            ]);
    }
}
