<?php

namespace App\Filament\Resources\Parcels\Schemas;

use App\Enums\Parcel\ParcelStatus;
use App\Enums\User\RoleType;
use App\Filament\Resources\Parcels\Pages\CreateParcel;
use App\Filament\Resources\Units\RelationManagers\ParcelsRelationManager;
use App\Models\LogisticPartner;
use App\Models\Residence;
use App\Models\Unit;
use App\Models\User;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class ParcelForm
{
    public static function configure(Schema $schema): Schema
    {
        $user = auth()->user();

        return $schema
            ->components([
                Section::make(__('parcel.parcel'))
                    ->description(__('parcel.parcel_detail'))
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Select::make('residence_id')
                            ->label(__('app.mooban_or_residence'))
                            ->options(function () use ($user) {
                                if ($user->hasRole('Property Management')) {
                                    return Residence::where('property_management_user_id', $user->id)->pluck('name', 'id')->toArray();
                                } elseif ($user->hasRole('Admin')) {
                                    return Residence::whereIn('residence_activation_status_id', [1, 6])->pluck('name', 'id')->toArray();
                                } else {
                                    return Residence::all()->pluck('name', 'id');
                                }
                            })
                            ->reactive()
                            ->searchable()
                            ->afterStateUpdated(function (Set $set) {
                                $set('unit_id', null);
                                $set('receiver_id', null);
                            })
                            ->required()
                            ->hidden(fn(Component $livewire): bool => $livewire instanceof ParcelsRelationManager),
                        Select::make('unit_id')
                            ->label(__('unit.unit_number'))
                            ->options(function (callable $get) {
                                if (auth()->user()->hasRole('Property Management')) {
                                    $residence_id = Residence::where('property_management_user_id', auth()->user()->id)->pluck('id');

                                    return Unit::where('residence_id', $residence_id)->pluck('unit_number', 'id');
                                } else {
                                    return Unit::where('residence_id', $get('residence_id'))->pluck('unit_number', 'id');
                                }
                            })
                            ->reactive()
                            ->searchable()
                            ->required()
                            ->hidden(fn(Component $livewire): bool => $livewire instanceof ParcelsRelationManager),
                        Select::make('receiver_id')
                            ->label(__('user.receiver'))
                            ->options(function (Component $livewire, callable $get) {
                                if (isset($livewire->ownerRecord)) {
                                    $users = [];

                                    $users = User::whereHas('roles', function ($query) {
                                        $query->whereIn('name', [RoleType::UNIT_OWNER->value, RoleType::UNIT_TENANT->value]);
                                    })->whereHas('units', function (Builder $builder) use ($livewire) {
                                        return $builder->where('unit_user.unit_id', $livewire->ownerRecord->id);
                                    })->pluck('name', 'id')->toArray();

                                    $users += [
                                        'All' => 'All',
                                        'Other' => 'Other',
                                    ];

                                    return $users;
                                } else {
                                    $users = [];
                                    $users = User::whereHas('roles', function ($query) {
                                        $query->whereIn('name', [RoleType::UNIT_OWNER->value, RoleType::UNIT_TENANT->value]);
                                    })->whereHas('units', function (Builder $builder) use ($get) {
                                        return $builder->where('unit_user.unit_id', $get('unit_id'));
                                    })->pluck('name', 'id')->toArray();

                                    $users += [
                                        'All' => 'All',
                                        'Other' => 'Other',
                                    ];

                                    return $users;
                                }
                            })
                            ->searchable()
                            ->reactive()
                            ->required(),
                        TextInput::make('receiver_name')
                            ->label(__('parcel.receiver_name'))
                            ->required()
                            ->reactive()
                            ->hidden(fn(Get $get) => $get('receiver_id') == null || $get('receiver_id') != 'Other')
                            ->maxLength(255),
                        Select::make('courier_id')
                            ->label(__('parcel.courier_company'))
                            ->options(LogisticPartner::all()->pluck('name', 'id'))
                            ->reactive()
                            ->searchable()
                            ->required(),
                        TextInput::make('tracking_no')
                            ->label(__('parcel.tracking_number'))
                            ->maxLength(50),
                        SpatieMediaLibraryFileUpload::make('image')
                            ->label(__('app.image'))
                            ->collection('parcel_images')
                            ->customProperties(['type' => 'parcel'])
                            ->disk('cos')
                            ->multiple()
                            ->openable(true)
                            ->downloadable(true),
                        Textarea::make('description')
                            ->label(__('app.description'))
                            ->maxLength(2000)
                            ->columnSpanFull(),

                        Fieldset::make(__('parcel.pickup_receiver'))
                            ->columnSpanFull()
                            ->columns(2)
                            ->schema([
                                TextInput::make('pickup_person_contact_no')
                                    ->label(__('parcel.pickup_person_phone_number'))
                                    ->required()
                                    ->placeholder('+66(000)000-00000')
                                    ->hidden(fn(Component $livewire): bool => $livewire instanceof CreateParcel),
                                TextInput::make('pickup_person_name')
                                    ->label(__('parcel.pickup_person_name'))
                                    ->maxLength(255)
                                    ->hidden(fn(Component $livewire, Get $get): bool => ($livewire instanceof CreateParcel) || $get('receiver') != null),
                                SpatieMediaLibraryFileUpload::make('signature_image')
                                    ->label(__('app.signature_image'))
                                    ->collection('signature_image')
                                    ->customProperties(['type' => 'signature'])
                                    ->disk('cos')
                                    ->image()
                                    ->openable(true)
                                    ->hidden(fn(Component $livewire): bool => $livewire instanceof CreateParcel),
                                Radio::make('status')
                                    ->label(__('app.status'))
                                    ->options([
                                        ParcelStatus::PENDING_PICK_UP->value => __('parcel.pending_pickup'),
                                        ParcelStatus::PICKED_UP->value => __('parcel.picked_up'),
                                        ParcelStatus::NOT_MY_PARCEL->value => __('parcel.not_my_parcel'),
                                    ])
                                    ->columns(3)
                                    ->required()
                                    ->hidden(fn(Component $livewire): bool => $livewire instanceof CreateParcel),
                                Hidden::make('created_by_mmb_user_id')
                                    ->default($user->id),
                            ]),
                    ])
            ]);
    }
}
