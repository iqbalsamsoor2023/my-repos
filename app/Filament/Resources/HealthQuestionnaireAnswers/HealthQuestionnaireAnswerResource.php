<?php

namespace App\Filament\Resources\HealthQuestionnaireAnswers;

use Filament\Schemas\Schema;
use App\Filament\Resources\HealthQuestionnaireAnswers\Pages\ListHealthQuestionnaireAnswers;
use App\Filament\Resources\HealthQuestionnaireAnswers\Pages\CreateHealthQuestionnaireAnswer;
use App\Filament\Resources\HealthQuestionnaireAnswers\Pages\EditHealthQuestionnaireAnswer;
use App\Filament\Resources\HealthQuestionnaireAnswers\Schemas\HealthQuestionnaireAnswerForm;
use App\Filament\Resources\HealthQuestionnaireAnswers\Tables\HealthQuestionnaireAnswersTable;
use App\Models\HealthQuestionnaireAnswer;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class HealthQuestionnaireAnswerResource extends Resource
{
    protected static ?string $model = HealthQuestionnaireAnswer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static bool $shouldRegisterNavigation = false;

    public static function getPluralModelLabel(): string
    {
        return __('menu.health_questionnaire_answers');
    }

    public static function form(Schema $schema): Schema
    {
        return HealthQuestionnaireAnswerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HealthQuestionnaireAnswersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHealthQuestionnaireAnswers::route('/'),
            'create' => CreateHealthQuestionnaireAnswer::route('/create'),
            'edit' => EditHealthQuestionnaireAnswer::route('/{record}/edit'),
        ];
    }
}
