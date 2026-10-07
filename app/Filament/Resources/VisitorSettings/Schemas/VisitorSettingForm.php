<?php

namespace App\Filament\Resources\VisitorSettings\Schemas;

use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class VisitorSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Visitor Setting'))
                    ->description(__('Visitor Setting Details'))
                    ->columnSpanFull()
                    ->columns(1)
                    ->schema([
                        // Forms\Components\Toggle::make('is_qr_active')
                        //     ->inline(false)
                        //     ->default(true),
                        // Forms\Components\Toggle::make('vs_qr_scan_out')
                        //     ->label(__('QR Scan Out'))
                        //     ->inline(false),
                        SpatieMediaLibraryFileUpload::make('pdpa')
                            ->label(__('app.pdpa'))
                            ->collection('document')
                            ->customProperties(['type' => 'document'])
                            ->disk('cos')
                            ->acceptedFileTypes(['application/pdf'])
                            ->openable(true),
                    ]),
            ]);
    }
}
