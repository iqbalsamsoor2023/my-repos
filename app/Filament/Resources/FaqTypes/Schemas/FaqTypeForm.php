<?php

namespace App\Filament\Resources\FaqTypes\Schemas;

use App\Models\Erp\FaqType;
use App\Models\Erp\Platform;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class FaqTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('FAQ Types')
                    ->columnSpanFull()
                    ->columns(3)
                    ->schema([
                        Select::make('platform_id')
                            ->label(__('app.platform'))
                            ->options(Platform::all()->pluck('name', 'id'))
                            ->required()
                            ->reactive(),
                        TextInput::make('name')
                            ->label(__('app.type'))
                            ->required()
                            ->maxLength(255)
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $set('slug', Str::slug($state));
                            }),
                        TextInput::make('name_in_thai')
                            ->label(__('app.type_in_thai'))
                            ->required()
                            ->maxLength(255),
                        Hidden::make('slug')
                            ->dehydrateStateUsing(fn (string $state): string => Str::of($state)->slug('-'))
                            ->required(),
                        Toggle::make('is_active')
                            ->label(__('app.is_active'))
                            ->required()
                            ->default(true)
                            ->inline(false),
                        Fieldset::make('FAQs')
                            ->columnSpanFull()
                            ->schema([
                                Repeater::make('faqs')
                                    ->label('')
                                    ->schema([
                                        Hidden::make('id'),
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('question')
                                                    ->label(__('user.question'))
                                                    ->required(),
                                                TextInput::make('question_th')
                                                    ->label(__('user.question_th'))
                                                    ->required(),
                                            ]),
                                        Grid::make(2)
                                            ->schema([
                                                RichEditor::make('answer')
                                                    ->label(__('user.answer'))
                                                    ->toolbarButtons([
                                                        'attachFiles',
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
                                                    ->fileAttachmentsDirectory(fn (?FaqType $record) => $record?->id ? 'faq-types/'.$record->id : 'faq-types/temp')
                                                    ->disableToolbarButtons(['codeBlock'])
                                                    ->required(),
                                                RichEditor::make('answer_th')
                                                    ->label(__('user.answer_th'))
                                                    ->toolbarButtons([
                                                        'attachFiles',
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
                                                    ->fileAttachmentsDirectory(fn (?FaqType $record) => $record?->id ? 'faq-types/'.$record->id : 'faq-types/temp')
                                                    ->disableToolbarButtons(['codeBlock'])
                                                    ->required(),
                                            ]),
                                    ])
                                    ->addActionLabel(__('Add FAQ'))
                                    ->collapsible()
                                    ->defaultItems(1)
                                    ->columnSpanFull()
                                    ->dehydrated()
                                    ->saveRelationshipsUsing(null),
                            ]),
                    ]),
            ]);
    }
}
