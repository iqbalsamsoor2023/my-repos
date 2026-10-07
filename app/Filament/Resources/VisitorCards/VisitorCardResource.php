<?php

namespace App\Filament\Resources\VisitorCards;

use App\Filament\Resources\VisitorCardResource\Pages;
use App\Filament\Resources\VisitorCards\Pages\CreateVisitorCard;
use App\Filament\Resources\VisitorCards\Pages\ListVisitorCards;
use App\Filament\Resources\VisitorCards\Pages\MultipleQrVisitorCard;
use App\Filament\Resources\VisitorCards\Pages\QrVisitorCard;
use App\Filament\Resources\Vms\RelationManagers\VisitorCardsRelationManager;
use App\Jobs\VisitorQrCode\CleanupVisitorQrBatchJob;
use App\Models\VisitorCard;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

use Livewire\Component;

class VisitorCardResource extends Resource
{
    protected static ?string $model = VisitorCard::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-device-tablet';

    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationGroup(): string
    {
        return __('menu.security_data');
    }

    public static function getModelLabel(): string
    {
        return __('visitor.visitor_card');
    }

    public static function getPluralModelLabel(): string
    {
        return __('menu.visitor_cards');
    }

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
            'index' => ListVisitorCards::route('/'),
            'create' => CreateVisitorCard::route('/create'),
            // 'edit' => Pages\EditVisitorCard::route('/{record}/edit'),
            'qr' => QrVisitorCard::route('/qr/{visitorCardId}'),
            'multiple-qr' => MultipleQrVisitorCard::route('/multiple-qr/{visitorCardIdsHash}'),
        ];
    }

    public static function getForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Visitor Card'))
                    ->schema([
                        Grid::make([
                            'sm' => 2,
                            'md' => 3,
                            'lg' => 4,
                            'xl' => 6,
                            '2xl' => 6,
                        ])
                            ->schema([
                                Fieldset::make(__('Residence Listing'))
                                    ->schema([
                                        Select::make('residence_id')
                                            ->label(__('app.residence_mooban'))
                                            ->relationship('residence', 'name')
                                            ->searchable(),
                                    ])
                                    ->columns(1),
                            ]),
                    ])
                    ->hidden(fn (Component $livewire): bool => $livewire instanceof VisitorCardsRelationManager),
            ]);
    }

    public static function getTable(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('residence.name')
                    ->searchable()
                    ->hidden(fn (Component $livewire): bool => $livewire instanceof VisitorCardsRelationManager),
                TextColumn::make('visitor_card_no')
                    ->searchable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('qr')
                    ->label(__('app.qr_code'))
                    ->icon('heroicon-o-qr-code')
                    ->url(fn (VisitorCard $record): string => route('filament.admin.resources.visitor-cards.qr', $record))
                    ->openUrlInNewTab(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
                BulkAction::make('multiple_qr')
                    ->label(__('app.qr_code'))
                    ->icon('heroicon-o-qr-code')
                    ->action(function (Collection $records) {

                        $batchId = (string) Str::uuid();
                
                        $ids = $records
                            ->pluck('id')
                            ->map(fn ($id) => (int) $id)
                            ->values()
                            ->all();
                
                        /*
                         * Store selected IDs.
                         */
                        Cache::put(
                            "visitor-card-qr-batch:{$batchId}",
                            $ids,
                            now()->addHours(6)
                        );

                        Cache::put(
                            "visitor-card-qr-total:{$batchId}",
                            count($ids),
                            now()->addHours(6)
                        );                        
                
                        /*
                         * Initial progress.
                         */
                        Cache::put(
                            "visitor-card-qr-completed:{$batchId}",
                            0,
                            now()->addHours(6)
                        );
                
                        /*
                         * Cleanup batch after 10 minutes.
                         */
                        CleanupVisitorQrBatchJob::dispatch($batchId)
                            ->delay(now()->addMinutes(10));
                
                        return redirect()->route(
                            'filament.admin.resources.visitor-cards.multiple-qr',
                            [
                                'visitorCardIdsHash' => $batchId,
                            ]
                        );
                    }),
                    // ->action(fn (Collection $records) => redirect()->route('filament.admin.resources.visitor-cards.multiple-qr', base64_encode(json_encode($records->map->id)))),
            ]);
    }
}
