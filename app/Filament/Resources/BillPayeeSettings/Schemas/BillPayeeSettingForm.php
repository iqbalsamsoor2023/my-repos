<?php

namespace App\Filament\Resources\BillPayeeSettings\Schemas;

use App\Filament\Resources\BillPayeeSettings\Pages\CreateBillPayeeSetting;
use App\Models\Bank;
use App\Models\BillPayeeSetting;
use App\Models\Residence;
use Closure;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Livewire\Component;

class BillPayeeSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        $user = auth()->user();

        return $schema
            ->components([
                Section::make(__('billing.bill_reminder_setting'))
                    ->description(__('billing.setting_details'))
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Fieldset::make(__('app.mooban_or_residence'))
                            ->columnSpanFull()
                            ->columns(1)
                            ->schema([
                                Select::make('residence_id')
                                    ->label(__('app.mooban_or_residence'))
                                    ->options(function () use ($user) {
                                        $query = Residence::query()->select(['id', 'name']);
                                
                                        if ($user->hasRole('Property Management')) {
                                            $query->where('property_management_user_id', $user->id);
                                        }
                                
                                        return $query->pluck('name', 'id')->toArray();
                                    })
                                    ->rules([
                                        function (Component $livewire) {
                                            return function (string $attribute, $value, Closure $fail) use ($livewire) {
                                                if ($livewire instanceof CreateBillPayeeSetting) {
                                                    $check_setting_is_exist = BillPayeeSetting::where('residence_id', $livewire->data['residence_id'])->exists();

                                                    if ($check_setting_is_exist) {
                                                        $fail('This Mooban setting already exist!');
                                                    }
                                                }
                                            };
                                        },
                                    ])
                                    ->reactive()
                                    ->searchable()
                                    ->required(),
                            ]),

                        Fieldset::make(__('billing.bank_account_info'))
                            ->columnSpanFull()
                            ->columns(1)
                            ->schema([
                                SpatieMediaLibraryFileUpload::make('qr_code_image')
                                    ->label(__('Qr Code Image'))
                                    ->collection('qr')
                                    ->disk('cos')
                                    ->image(),
                                Repeater::make('accounts')
                                    ->label('')
                                    ->relationship()
                                    ->schema([
                                        Select::make('bank_id')
                                            ->label(__('billing.bank'))
                                            ->options(Bank::all()->pluck('name_in_thai', 'id'))
                                            ->required()
                                            ->reactive(),
                                        TextInput::make('payee_account_name')
                                            ->label(__('billing.account_name'))
                                            ->required()
                                            ->maxLength(255),
                                        TextInput::make('payee_account_name_th')
                                            ->label(__('billing.account_name_thai'))
                                            ->maxLength(255),
                                        TextInput::make('payee_account_number')
                                            ->label(__('billing.account_number'))
                                            ->required()
                                            ->numeric(),
                                    ])
                                    ->columns(4)
                                    ->minItems(1)
                                    ->maxItems(3),
                            ]),

                        Fieldset::make(__('billing.juristic_info'))
                            ->columnSpanFull()
                            ->schema([
                                TextInput::make('payee_name')
                                    ->label(__('app.name'))
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('payee_name_th')
                                    ->label(__('app.name_th'))
                                    ->maxLength(255),
                                TextInput::make('payee_email')
                                    ->label(__('app.email'))
                                    ->email()
                                    ->placeholder('user@mymooban.com')
                                    ->maxLength(255),
                                TextInput::make('payee_phone_no')
                                    ->label(__('app.phone_number'))
                                    ->placeholder('+66(000)000-00000'),
                                Textarea::make('payee_address')
                                    ->label(__('app.address')),
                                Textarea::make('payee_address_th')
                                    ->label(__('app.address_th')),
                            ]),

                        Fieldset::make('Manage Bill Format')
                            ->columnSpanFull()
                            ->schema([
                                Textarea::make('remark')
                                    ->label(__('app.remark')),
                                Textarea::make('remark_th')
                                    ->label(__('app.remark_thai')),
                            ]),
                    ])
            ]);
    }
}
