<?php

namespace App\Filament\Resources\Pdpas\Tables;

use App\Enums\ResourceMaterial\ResourceTypeEnum;
use App\Models\Erp\StaticContent;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Support\Facades\App;

class PdpasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->query(StaticContent::where('type', ResourceTypeEnum::PRIVACY_POLICY->value))
            ->columns([
                TextColumn::make('content')
                    ->label(__('app.content'))
                    ->formatStateUsing(function ($state, $record) {
                        $locale = App::getLocale();
                        $content = $record->getTranslation('content', $locale, true);

                        return strip_tags($content);
                    })
                    ->limit(200)
                    ->toggleable(),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                ]),
            ], position: RecordActionsPosition::BeforeColumns);
    }
}
