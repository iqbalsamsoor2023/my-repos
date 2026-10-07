<?php

namespace App\Filament\Resources\Amenities\Schemas;

use App\Filament\Resources\Amenities\Pages\CreateAmenity;
use App\Filament\Resources\Amenities\Pages\EditAmenity;
use App\Models\Amenity;
use Closure;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Livewire\Component;

class AmenityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('maintenance.amenity'))
                    ->description(__('maintenance.amenity_detail'))
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('amenity_name')
                            ->label(__('maintenance.amenity_name'))
                            ->required()
                            ->maxLength(255)
                            ->rules([
                                function (Component $livewire) {
                                    return function (string $attribute, $value, Closure $fail) use ($livewire) {
                                        if ($livewire instanceof CreateAmenity || (isset($livewire->mountedActions[0]) && $livewire->mountedActions[0] == 'create')) {
                                            $residenceId = $livewire->getRelationship()->getParent()->id;

                                            $checkAmenityExist = Amenity::where('residence_id', $residenceId)
                                                ->where('amenity_name', $value)
                                                ->first();

                                            if ($checkAmenityExist) {
                                                $fail('The name has already been taken');
                                            }
                                        } elseif ($livewire instanceof EditAmenity || (isset($livewire->mountedActions[0]) && $livewire->mountedActions[0] == 'edit' && $livewire->getModel()->exists)) {
                                            $residenceId = $livewire->record->residence_id;

                                            $currentAmenityId = $livewire->record->id ?? null;

                                            $checkAmenityExist = Amenity::where('residence_id', $residenceId)
                                                ->where('amenity_name', $value)
                                                ->when($currentAmenityId, function ($query) use ($currentAmenityId) {
                                                    return $query->where('id', '!=', $currentAmenityId); // Exclude the current record during edit
                                                })
                                                ->first();

                                            if ($checkAmenityExist) {
                                                $fail('The name has already been taken');
                                            }
                                        }
                                    };
                                },
                            ]),
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
                                'month' => 'Month(s)',
                                'year' => 'Year(s)',
                            ])
                            ->required()
                            ->visible(fn (Get $get) => $get('has_warranty') == true),
                        TextInput::make('supplier')
                            ->label(__('maintenance.supplier'))
                            ->required()
                            ->maxLength(255)
                            ->visible(fn (Get $get) => $get('has_warranty') == true),
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
                    ])
            ]);
    }
}
