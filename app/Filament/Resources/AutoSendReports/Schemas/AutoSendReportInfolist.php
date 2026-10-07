<?php

namespace App\Filament\Resources\AutoSendReports\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AutoSendReportInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Auto Send Reports'))
                    ->columnSpanFull()
                    ->schema([
                        Grid::make()
                            ->columns(2)
                            ->schema([
                                TextEntry::make('email')
                                    ->label(__('app.email'))
                                    ->inlineLabel()
                                    ->color('primary'),
                                TextEntry::make('residence')
                                    ->label(__('app.mooban_or_residence'))
                                    ->getStateUsing(function ($record) {
                                        $locale = app()->getLocale();
                                    
                                        return $locale === 'th'
                                            ? ($record->residence?->name_th ?? '-')
                                            : ($record->residence?->name ?? '-');
                                    })
                                    ->inlineLabel()
                                    ->color('primary'),
                                TextEntry::make('time')
                                    ->label(__('app.time'))
                                    ->inlineLabel()
                                    ->color('primary'),
                                TextEntry::make('hour')
                                    ->label(__('app.hour'))
                                    ->inlineLabel()
                                    ->color('primary'),
                                TextEntry::make('module_type')
                                    ->label(__('app.module_type'))
                                    ->inlineLabel()
                                    ->color('primary'),
                            ]),
                    ])
            ]);
    }
}
