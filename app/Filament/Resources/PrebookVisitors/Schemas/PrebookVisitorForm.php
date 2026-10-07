<?php

namespace App\Filament\Resources\PrebookVisitors\Schemas;

use App\Enums\User\RoleType;
use App\Enums\Vehicle\VehicleColor;
use App\Enums\Visitor\ArrivalType;
use App\Enums\Visitor\IdType;
use App\Enums\Visitor\VehicleType;
use App\Filament\Resources\PrebookVisitors\Pages\CreatePrebookVisitor;
use App\Filament\Resources\PrebookVisitors\Pages\ViewPrebookVisitor;
use App\Models\Erp\ThailandProvince;
use App\Models\Unit;
use App\Models\User;
use App\Models\VehicleBrand;
use App\Models\VisitorPurpose;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\App;
use Livewire\Component;

class PrebookVisitorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('visitor.prebook_visitor'))
                    ->description(__('visitor.prebook_visitor_details'))
                    ->columnSpanFull()
                    ->schema([
                        Fieldset::make(__('visitor.arrival_informations'))
                            ->schema([
                                Select::make('residence_id')
                                    ->label(__('app.mooban_or_residence'))
                                    ->options(function (Component $livewire) {
                                        if ($livewire instanceof CreatePrebookVisitor) {
                                            return list_create_residences();
                                        }

                                        return list_residences();
                                    })
                                    ->default(function () {
                                        $user = auth()->user();

                                        if ($user->hasRole('Property Management')) {
                                            return $user->propertyManagement?->id;
                                        }

                                        return null;
                                    })
                                    ->reactive()
                                    ->searchable()
                                    ->afterStateUpdated(function (Set $set) {
                                        $set('unit_id', null);
                                        $set('user_id', null);
                                    })
                                    ->required(),
                                Select::make('unit_id')
                                    ->label(__('unit.unit_number'))
                                    ->relationship('unit', 'unit_number', function (callable $get) {
                                        return Unit::where('residence_id', $get('residence_id'));
                                    })
                                    ->reactive()
                                    ->preload()
                                    ->searchable()
                                    ->required(),
                                Select::make('user_id')
                                    ->label(__('app.resident'))
                                    ->options(function (Component $livewire, callable $get) {
                                        if (isset($livewire->ownerRecord)) {
                                            return User::whereHas('roles', function ($query) {
                                                $query->whereIn('name', [RoleType::UNIT_OWNER->value, RoleType::UNIT_TENANT->value]);
                                            })->whereHas('units', function (Builder $builder) use ($livewire) {
                                                return $builder->where('unit_user.unit_id', $livewire->ownerRecord->id)->whereNull('unit_user.deleted_at');
                                            })->pluck('name', 'id');
                                        } else {
                                            return User::whereHas('roles', function ($query) {
                                                $query->whereIn('name', [RoleType::UNIT_OWNER->value, RoleType::UNIT_TENANT->value]);
                                            })->whereHas('units', function (Builder $builder) use ($get) {
                                                return $builder->where('unit_user.unit_id', $get('unit_id'))->whereNull('unit_user.deleted_at');
                                            })->pluck('name', 'id');
                                        }
                                    })
                                    ->searchable()
                                    ->required(),
                                Select::make('visitor_purpose')
                                    ->label(__('visitor.purpose_of_visit'))
                                    ->options([
                                        'Receive/Delivery' => __('visitor.receive_or_delivery'),
                                        'Drop Off/Pick Up' => __('visitor.dropoff_or_pickup'),
                                        'Contractor/Worker' => __('visitor.contractor_or_worker'),
                                        'Visitor Parking' => __('visitor.visitor_parking'),
                                        'VIP' => __('visitor.vip'),
                                        'Other' => __('app.other'),
                                    ])
                                    ->searchable()
                                    ->required()
                                    ->reactive(),
                                Select::make('visitor_purpose_other')
                                    ->label(__('Purpose of Visit for Other Option'))
                                    ->options(function (callable $get) {
                                        return VisitorPurpose::where('residence_id', $get('residence_id'))->pluck('purpose', 'purpose');
                                    })
                                    ->searchable()
                                    ->required(fn (Get $get) => $get('visitor_purpose') == 'Other')
                                    ->hidden(fn (Get $get) => $get('visitor_purpose') != 'Other' || $get('visitor_purpose') == null),
                                Radio::make('arrival_type')
                                    ->label(__('visitor.arrival_type'))
                                    ->options(
                                        collect(ArrivalType::cases())
                                            ->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()])
                                            ->toArray()
                                    )
                                    ->columns(5)
                                    ->reactive()
                                    ->required(),
                                Toggle::make('is_multiple_entry')
                                    ->label(__('visitor.is_multiple_entry'))
                                    ->inline(false)
                                    ->reactive(),
                                Fieldset::make(__('vehicle.vehicle_details'))
                                    ->columnSpanFull()
                                    ->schema([
                                        Select::make('vehicle_type')
                                            ->label(__('vehicle.vehicle_type'))
                                            ->options(
                                                collect(VehicleType::cases())->mapWithKeys(fn ($case) => [
                                                    $case->value => $case->getLabel(),
                                                ])->toArray()
                                            )
                                            ->required()
                                            ->searchable()
                                            ->reactive(),
                                        TextInput::make('vehicle_plate_no')
                                            ->label(__('vehicle.vehicle_plate_number'))
                                            ->maxLength(20)
                                            ->required()
                                            ->afterStateUpdated(fn ($state, callable $set, callable $get) => updateVehicleInfo($set, $get)),
                                        Select::make('province_id')
                                            ->label(__('app.province'))
                                            ->options(function () {
                                                return ThailandProvince::all()->mapWithKeys(function ($province) {
                                                    return [
                                                        $province->id => 'th-'.$province->code.':'.$province->name_in_english,
                                                    ];
                                                })->toArray();
                                            })
                                            ->searchable()
                                            ->live()
                                            ->afterStateUpdated(fn ($state, callable $set, callable $get) => updateVehicleInfo($set, $get)),
                                        Select::make('vehicle_brand_id')
                                            ->label(__('vehicle.brand'))
                                            ->options(
                                                App::getLocale() === 'th'
                                                    ? VehicleBrand::all()->filter(fn ($b) => $b->name_th)->pluck('name_th', 'id')
                                                    : VehicleBrand::all()->filter(fn ($b) => $b->name)->pluck('name', 'id')
                                            )
                                            ->live()
                                            ->afterStateUpdated(fn ($state, callable $set, callable $get) => updateVehicleInfo($set, $get)),
                                        Select::make('vehicle_color')
                                            ->label(__('vehicle.color'))
                                            ->options(
                                                collect(VehicleColor::cases())
                                                    ->mapWithKeys(fn ($color) => [
                                                        $color->colorCode() => $color->label(),
                                                    ])
                                                    ->toArray()
                                            )
                                            ->searchable()
                                            ->preload()
                                            ->live()
                                            ->afterStateUpdated(fn ($state, callable $set, callable $get) => updateVehicleInfo($set, $get)),
                                        Hidden::make('vehicle_info'),
                                    ])
                                    ->columns(3)
                                    ->hidden(fn (Get $get) => in_array($get('arrival_type'), [null, ArrivalType::WALK_IN->value])),
                                TextEntry::make('is_qr_code_expired')
                                    ->label(__('visitor.is_qr_code_expired'))
                                    ->state(fn ($record) => $record->is_qr_code_expired == true ? 'Yes' : 'No')
                                    ->visible(fn (Component $livewire): bool => $livewire instanceof ViewPrebookVisitor),
                                DateTimePicker::make('validity_start_date')
                                    ->label(__('visitor.validity_start_date'))
                                    ->native(false)
                                    ->seconds(false)
                                    ->required(),
                                DateTimePicker::make('validity_end_date')
                                    ->label(__('visitor.validity_end_date'))
                                    ->native(false)
                                    ->seconds(false)
                                    ->hidden(fn (Get $get) => $get('is_multiple_entry') == false || $get('is_multiple_entry') == null),
                            ]),

                        Fieldset::make("Visitor's Personal Informations")
                            ->columns(2)
                            ->relationship('visitor')
                            ->schema([
                                TextInput::make('name')
                                    ->label(__('app.name'))
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('contact_no')
                                    ->label(__('app.phone_number'))
                                    ->placeholder('+66(000)000-00000'),
                                Radio::make('id_type')
                                    ->label(__('user.id_type'))
                                    ->options([
                                        IdType::IC->value => __(IdType::IC->name),
                                        IdType::PASSPORT->value => __('user.'.strtolower(IdType::PASSPORT->name)),
                                        IdType::DRIVING_LICENSE->value => __('user.'.strtolower(IdType::DRIVING_LICENSE->name)),
                                    ])
                                    ->columns(4)
                                    ->required()
                                    ->reactive(),
                                TextInput::make('id_number')
                                    ->label(__('user.id_number'))
                                    ->maxLength(20),
                            ]),

                        Fieldset::make("Visitor's Extra Informations")
                            ->schema([
                                TextInput::make('passenger_count')
                                    ->label(__('app.passenger_count'))
                                    ->numeric()
                                    ->minValue(1),
                                TextInput::make('company_name')
                                    ->label(__('app.company_name'))
                                    ->maxLength(255),
                                Textarea::make('remark')
                                    ->label(__('app.remark'))
                                    ->maxLength(255),
                            ]),
                    ]),
            ]);
    }
}
