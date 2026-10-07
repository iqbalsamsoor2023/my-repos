<?php

namespace App\Filament\Resources\Pdpas\Schemas;

use App\Enums\ResourceMaterial\ResourceTypeEnum;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PdpaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('PDPA')
                    ->columnSpanFull()
                    ->schema([
                        Hidden::make('type')
                            ->default(ResourceTypeEnum::PRIVACY_POLICY->value),
                        RichEditor::make('content')
                            ->label(__('app.content'))
                            ->toolbarButtons([
                                'blockquote',
                                'bold',
                                'bulletList',
                                'h2',
                                'h3',
                                'italic',
                                'link',
                                'orderedList',
                                'redo',
                                'strike',
                                'underline',
                                'undo',
                            ])
                            ->disableToolbarButtons([
                                'attachFiles',
                                'codeBlock',
                            ])
                            ->columnSpanFull()
                            ->required(),
                    ])
            ]);
    }
}
