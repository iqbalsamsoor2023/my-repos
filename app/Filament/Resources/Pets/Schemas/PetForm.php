<?php

namespace App\Filament\Resources\Pets\Schemas;

use App\Enums\Pet\PetType;
use App\Enums\User\RoleType;
use App\Filament\Resources\Pets\Pages\CreatePet;
use App\Filament\Resources\Units\RelationManagers\PetsRelationManager;
use App\Models\Residence;
use App\Models\Unit;
use App\Models\User;
use App\Policies\PetPolicy;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class PetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('pet.pet'))
                    ->description(__('pet.pet_detail'))
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Fieldset::make(__('app.details'))
                            ->columnSpanFull()
                            ->schema([
                                Select::make('residence_id')
                                    ->label(__('app.mooban_or_residence'))
                                    ->options(function (Component $livewire) {
                                        if ($livewire instanceof CreatePet) {
                                            return list_create_residences();
                                        }

                                        return list_residences();
                                    })
                                    ->reactive()
                                    ->searchable()
                                    ->afterStateUpdated(function (Set $set) {
                                        $set('unit_id', null);
                                        $set('user_id', null);
                                    })
                                    ->hidden(fn (Component $livewire): bool => $livewire instanceof PetsRelationManager)
                                    ->required(fn (Component $livewire): bool => $livewire instanceof CreatePet),
                                Select::make('unit_id')
                                    ->label(__('unit.unit'))
                                    ->options(function (callable $get) {
                                        if (PetPolicy::isPropertyManager(Auth::user())) {
                                            $residenceIds = Residence::where('property_management_user_id', '=', Auth::id(), 'and')
                                                ->pluck('id')
                                                ->all();

                                            if (empty($residenceIds)) {
                                                return [];
                                            }

                                            return Unit::whereIn('residence_id', $residenceIds, 'and', false)->pluck('unit_number', 'id');
                                        } else {
                                            return Unit::where('residence_id', '=', $get('residence_id'), 'and')->pluck('unit_number', 'id');
                                        }
                                    })
                                    ->reactive()
                                    ->searchable()
                                    ->hidden(fn (Component $livewire): bool => $livewire instanceof PetsRelationManager)
                                    ->required(fn (Component $livewire): bool => $livewire instanceof CreatePet),
                                Select::make('user_id')
                                    ->label(__('app.resident'))
                                    ->options(function (Component $livewire, callable $get) {
                                        if (isset($livewire->ownerRecord)) {
                                            return User::whereHas('roles', function ($query) {
                                                $query->whereIn('name', [RoleType::UNIT_OWNER->value, RoleType::UNIT_TENANT->value]);
                                            })->whereHas('units', function (Builder $builder) use ($livewire) {
                                                return $builder->where('unit_user.unit_id', $livewire->ownerRecord->id);
                                            })->pluck('name', 'id');
                                        } else {
                                            return User::whereHas('roles', function ($query) {
                                                $query->whereIn('name', [RoleType::UNIT_OWNER->value, RoleType::UNIT_TENANT->value]);
                                            })->whereHas('units', function (Builder $builder) use ($get) {
                                                return $builder->where('unit_user.unit_id', $get('unit_id'));
                                            })->pluck('name', 'id');
                                        }
                                    })
                                    ->searchable()
                                    ->required(),
                                Select::make('type')
                                    ->label(__('app.type'))
                                    ->options(PetType::options())
                                    ->searchable()
                                    ->required(),
                                TextInput::make('breed')
                                    ->label(__('pet.breed'))
                                    ->required()
                                    ->maxLength(255),
                                Select::make('year')
                                    ->label(__('pet.year_of_birth'))
                                    ->options(array_combine(range(date('Y'), 1969), range(date('Y'), 1969)))
                                    ->searchable()
                                    ->required(),
                                Hidden::make('created_by')
                                    ->default(fn (): ?int => Auth::id()),
                            ]),

                        Fieldset::make(__('app.images'))
                            ->columnSpanFull()
                            ->columns(3)
                            ->schema([
                                SpatieMediaLibraryFileUpload::make('back')
                                    ->label(__('pet.back'))
                                    ->translateLabel()
                                    ->collection('back')
                                    ->customProperties(['side' => 'back'])
                                    ->imageCropAspectRatio('2:2')
                                    ->disk('cos')
                                    ->image()
                                    ->openable(true)
                                    ->required(),
                                SpatieMediaLibraryFileUpload::make('bottom')
                                    ->label(__('pet.bottom'))
                                    ->translateLabel()
                                    ->collection('bottom')
                                    ->customProperties(['side' => 'bottom'])
                                    ->imageCropAspectRatio('2:2')
                                    ->disk('cos')
                                    ->image()
                                    ->openable(true)
                                    ->required(),
                                SpatieMediaLibraryFileUpload::make('front')
                                    ->label(__('pet.front'))
                                    ->translateLabel()
                                    ->collection('front')
                                    ->customProperties(['side' => 'front'])
                                    ->imageCropAspectRatio('2:2')
                                    ->disk('cos')
                                    ->image()
                                    ->openable(true)
                                    ->required(),
                                SpatieMediaLibraryFileUpload::make('left')
                                    ->label(__('pet.left'))
                                    ->translateLabel()
                                    ->collection('left')
                                    ->customProperties(['side' => 'left'])
                                    ->imageCropAspectRatio('2:2')
                                    ->disk('cos')
                                    ->image()
                                    ->openable(true)
                                    ->required(),
                                SpatieMediaLibraryFileUpload::make('right')
                                    ->label(__('pet.right'))
                                    ->translateLabel()
                                    ->collection('right')
                                    ->customProperties(['side' => 'right'])
                                    ->imageCropAspectRatio('2:2')
                                    ->disk('cos')
                                    ->image()
                                    ->openable(true)
                                    ->required(),
                                SpatieMediaLibraryFileUpload::make('top')
                                    ->label(__('pet.top'))
                                    ->translateLabel()
                                    ->collection('top')
                                    ->customProperties(['side' => 'top'])
                                    ->imageCropAspectRatio('2:2')
                                    ->disk('cos')
                                    ->image()
                                    ->openable(true)
                                    ->required(),
                            ]),
                    ]),
            ]);
    }
}
