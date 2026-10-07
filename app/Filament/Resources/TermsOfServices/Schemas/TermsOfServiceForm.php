<?php

namespace App\Filament\Resources\TermsOfServices\Schemas;

use App\Enums\ResourceMaterial\ResourceTypeEnum;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TermsOfServiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('menu.terms_of_service'))
                    ->columnSpanFull()
                    ->schema([
                        Hidden::make('type')
                            ->default(ResourceTypeEnum::TERMS_OF_SERVICE->value),
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
