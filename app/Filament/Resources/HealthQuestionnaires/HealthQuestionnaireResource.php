<?php

namespace App\Filament\Resources\HealthQuestionnaires;

use App\Filament\Resources\HealthQuestionnaires\Pages\CreateHealthQuestionnaire;
use App\Filament\Resources\HealthQuestionnaires\Pages\EditHealthQuestionnaire;
use App\Filament\Resources\HealthQuestionnaires\Pages\ListHealthQuestionnaires;
use App\Filament\Resources\HealthQuestionnaires\RelationManagers\HealthQuestionnaireAnswersRelationManager;
use App\Filament\Resources\HealthQuestionnaires\Schemas\HealthQuestionnaireForm;
use App\Filament\Resources\HealthQuestionnaires\Tables\HealthQuestionnairesTable;
use App\Models\HealthQuestionnaire;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class HealthQuestionnaireResource extends Resource
{
    protected static ?string $model = HealthQuestionnaire::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleBottomCenterText;

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): string
    {
        return __('menu.user_management');
    }

    public static function getModelLabel(): string
    {
        return __('menu.userManagement.manage_health_questionnaires');
    }

    public static function form(Schema $schema): Schema
    {
        return HealthQuestionnaireForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HealthQuestionnairesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            HealthQuestionnaireAnswersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHealthQuestionnaires::route('/'),
            'create' => CreateHealthQuestionnaire::route('/create'),
            'edit' => EditHealthQuestionnaire::route('/{record}/edit'),
        ];
    }
}
