<?php

namespace App\Filament\Resources\IncidentReports\Schemas;

use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class IncidentReportInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Incident Report Details'))
                    ->columnSpanFull()
                    ->schema([
                        Grid::make()
                            ->columns(2)
                            ->schema([
                                TextEntry::make('residence.sgocCompany.name')
                                    ->label(__('company.company_name'))
                                    ->getStateUsing(function ($record) {
                                        $locale = app()->getLocale();
                                    
                                        return $locale === 'th'
                                            ? ($record->residence?->sgocCompany?->name_th ?? '-')
                                            : ($record->residence?->sgocCompany?->name ?? '-');
                                    })
                                    ->inlineLabel()
                                    ->weight('bold'),
                            ])->visible(auth()->user()->hasAnyRole(['Super Admin', 'Admin'])),

                        Grid::make()
                            ->columns(2)
                            ->columnSpanFull()
                            ->schema([
                                TextEntry::make('residence')
                                    ->label(__('app.mooban_or_residence'))
                                    ->getStateUsing(function ($record) {
                                        $locale = app()->getLocale();
                                    
                                        return $locale === 'th'
                                            ? ($record->residence?->name_th ?? '-')
                                            : ($record->residence?->name ?? '-');
                                    })
                                    ->inlineLabel()
                                    ->color('primary')
                                    ->weight('bold'),
                                TextEntry::make('unit')
                                    ->label(__('unit.unit_number'))
                                    ->getStateUsing(fn($record) => $record->unit?->unit_number)
                                    ->inlineLabel(),
                                TextEntry::make('createdBy.name')
                                    ->label(__('user.security_guard_staff'))
                                    ->inlineLabel(),
                                TextEntry::make('title')
                                    ->label(__('app.title'))
                                    ->inlineLabel(),
                                TextEntry::make('description')
                                    ->label(__('app.description'))
                                    ->inlineLabel(),
                                SpatieMediaLibraryImageEntry::make('image')
                                    ->label(__('app.image'))
                                    ->columnSpanFull()
                                    ->disk('cos')
                                    ->imageHeight(300)
                            ]),
                    ])
            ]);
    }
}
