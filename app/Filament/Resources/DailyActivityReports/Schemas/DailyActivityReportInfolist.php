<?php

namespace App\Filament\Resources\DailyActivityReports\Schemas;

use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DailyActivityReportInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('menu.daily_activity_reports'))
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('residence.name')
                            ->label(__('app.mooban_or_residence'))
                            ->inlineLabel()
                            ->getStateUsing(function ($record) {
                                return app()->getLocale() === 'th'
                                    ? $record?->residence->name_th
                                    : $record?->residence->name;
                            })
                            ->color('primary'),
                        TextEntry::make('reported_by_name')
                            ->label(__('user.security_guard_staff'))
                            ->inlineLabel(),
                        TextEntry::make('start_at')
                            ->label(__('app.start_at'))
                            ->inlineLabel(),
                        TextEntry::make('end_at')
                            ->label(__('app.end_at'))
                            ->inlineLabel(),
                        TextEntry::make('examined_person_names')
                            ->label(__('app.examined_persons'))
                            ->inlineLabel()
                            ->default(fn ($record) => is_array($record->examined_person_names)
                                ? implode(', ', $record->examined_person_names)
                                : $record->examined_person_names),
                        SpatieMediaLibraryImageEntry::make('image')
                            ->label(__('app.image'))
                            ->inlineLabel()
                            ->disk('cos')
                            ->collection('default')
                            ->imageHeight(300),
                    ]),

                Section::make(__('report.reports'))
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('questionnaires')
                            ->label(__('report.report_details'))
                            ->inlineLabel()
                            ->getStateUsing(function ($record) {
                                $items = $record?->reports ?? [];

                                if (! is_array($items)) {
                                    return '-';
                                }

                                return collect($items)
                                    ->map(function ($item) {
                                        $title = $item['title'] ?? '-';
                                        $answer = $item['answer'] ?? '-';
                                        $remark = $item['remark'] ?? '-';

                                        return "<div style='margin-bottom: 6px;'>
                                                    <strong>{$title}</strong><br>
                                                    <span style='color: #555;'>Answer: {$answer}</span><br>
                                                    <span style='color: #555;'>Remark: {$remark}</span>
                                                </div>";
                                    })
                                    ->join('');
                            })
                            ->html(),
                    ]),
            ]);
    }
}
