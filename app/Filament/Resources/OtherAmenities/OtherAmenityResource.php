<?php

namespace App\Filament\Resources\OtherAmenities;

use Filament\Schemas\Schema;
use App\Filament\Resources\OtherAmenities\Pages\ListOtherAmenities;
use App\Filament\Resources\OtherAmenities\Pages\CreateOtherAmenity;
use App\Filament\Resources\OtherAmenities\Pages\EditOtherAmenity;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Fieldset;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\OtherAmenityResource\Pages;
use App\Models\OtherAmenity;
use Closure;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;
use Livewire\Component;

class OtherAmenityResource extends Resource
{
    protected static ?string $model = OtherAmenity::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-hashtag';

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Schema $schema): Schema
    {
        return static::getForm($schema);
    }

    public static function table(Table $table): Table
    {
        return static::getTable($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOtherAmenities::route('/'),
            'create' => CreateOtherAmenity::route('/create'),
            'edit' => EditOtherAmenity::route('/{record}/edit'),
        ];
    }

    public static function getForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('menu.setting'))
                    ->columnSpanFull()
                    ->schema([
                        Fieldset::make(__('app.details'))
                            ->schema([
                                SpatieMediaLibraryFileUpload::make('handbook')
                                    ->label(__('maintenance.warranty_handbook'))
                                    ->collection('document')
                                    ->customProperties(['attachment' => 'warranty_handbook'])
                                    ->acceptedFileTypes(['application/pdf'])
                                    ->disk('cos')
                                    ->openable(true)
                                    ->downloadable(true),
                                Toggle::make('is_other_amenity')
                                    ->label(__('maintenance.other_enabled'))
                                    ->reactive()
                                    ->inline(false)
                                    ->rules([
                                        function (Component $livewire) {
                                            if ($livewire->mountedActions[0] == 'create') {
                                                return function (string $attribute, $value, Closure $fail) use ($livewire) {
                                                    $check_warranty_is_exist = OtherAmenity::where('residence_id', $livewire->getRelationship()->getParent()->id)
                                                        ->first();
                                                    if ($check_warranty_is_exist) {
                                                        $fail('The warranty already exist');
                                                    }
                                                };
                                            }
                                        },
                                    ]),
                                Textarea::make('remark')
                                    ->label(__('app.remark'))
                                    ->required(fn(Get $get) => $get('is_other_amenity') == true)
                                    ->reactive()
                                    ->maxLength(255)
                                    ->visible(fn(Get $get) => $get('is_other_amenity') == true),
                                Toggle::make('is_show_warranty_reminder')
                                    ->label(__('maintenance.warranty_expiry_alert'))
                                    ->reactive()
                                    ->inline(false),
                                TextInput::make('remind_day')
                                    ->label(__('maintenance.remind_day'))
                                    ->numeric()
                                    ->suffix('days before expiry')
                                    ->visible(fn(Get $get) => $get('is_show_warranty_reminder') == true),
                            ]),
                    ]),

            ]);
    }

    public static function getTable(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('handbook')
                    ->label(__('maintenance.warranty_handbook'))
                    ->collection('document')
                    ->toggleable(),
                IconColumn::make('is_other_amenity')
                    ->label(__('maintenance.other_enabled'))
                    ->boolean()
                    ->toggleable(),
                TextColumn::make('remark')
                    ->label(__('app.popup_message'))
                    ->limit(30)
                    ->toggleable(),
                IconColumn::make('is_show_warranty_reminder')
                    ->label(__('maintenance.warranty_expiry_alert'))
                    ->boolean()
                    ->toggleable(),
                TextColumn::make('remind_day')
                    ->label(__('maintenance.remind_day'))
                    ->suffix(' days before expiry'),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }
}
