<?php

namespace App\Filament\Resources;

use App\Enums\LogisticPartner\ModesType;
use App\Filament\Resources\LogisticPartners\Pages;
use App\Models\LogisticPartner;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Group;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Enums\RecordActionsPosition;
use Illuminate\Database\Eloquent\Model;

class LogisticPartnerResource extends Resource
{
    protected static ?string $model = LogisticPartner::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Schema $schema): Schema
    {
        return static::getForm($schema);
    }

    public static function table(Table $table): Table
    {
        return static::getTable($table);
    }

    public static function getForm(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('app.companies_logo'))
                ->description(__('app.companies_logo_detail'))
                ->columnSpanFull()
                ->schema([
                    Group::make([
                        TextInput::make('name')
                            ->label(__('app.name'))
                            ->required()
                            ->unique(ignorable: fn (?Model $record): ?Model => $record)
                            ->maxLength(255),

                        Select::make('modes')
                            ->label(__('parcel.modes'))
                            ->options(ModesType::options())
                            ->searchable()
                            ->required(),
                    ])->columns(2),

                    SpatieMediaLibraryFileUpload::make('image')
                        ->label(__('app.image'))
                        ->disk('cos')
                        ->image()
                        ->collection('default')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function getTable(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\SpatieMediaLibraryImageColumn::make('image')
                    ->label(__('app.image'))
                    ->collection('default')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('modes')
                    ->label(__('parcel.modes'))
                    ->formatStateUsing(fn (?int $state) => ModesType::tryFrom($state)?->getLabel())
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label(__('app.name'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('app.created_at'))
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('app.updated_at'))
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                ]),
            ], position: RecordActionsPosition::BeforeColumns)
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLogisticPartners::route('/'),
            'create' => Pages\CreateLogisticPartner::route('/create'),
            'edit' => Pages\EditLogisticPartner::route('/{record}/edit'),
        ];
    }
}
