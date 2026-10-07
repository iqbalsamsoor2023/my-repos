<?php

namespace App\Filament\Resources\EmailCampaigns;

use App\Filament\Resources\EmailCampaigns\Forms\EmailCampaignForm;
use App\Filament\Resources\EmailCampaigns\Pages\CreateEmailCampaign;
use App\Filament\Resources\EmailCampaigns\Pages\EditEmailCampaign;
use App\Filament\Resources\EmailCampaigns\Pages\ListEmailCampaigns;
use App\Filament\Resources\EmailCampaigns\Pages\ViewEmailCampaign;
use App\Filament\Resources\EmailCampaigns\Tables\EmailCampaignTable;
use App\Models\EmailCampaign;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class EmailCampaignResource extends Resource
{
    protected static ?string $model = EmailCampaign::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-envelope';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): string
    {
        return __('menu.administrator_management');
    }

    public static function getNavigationLabel(): string
    {
        return __('email-campaign.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('email-campaign.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('email-campaign.plural_model_label');
    }

    public static function form(Schema $schema): Schema
    {
        return EmailCampaignForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EmailCampaignTable::configure($table);            
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmailCampaigns::route('/'),
            'create' => CreateEmailCampaign::route('/create'),
            'view' => ViewEmailCampaign::route('/{record}'),
            'edit' => EditEmailCampaign::route('/{record}/edit'),
        ];
    }
}
