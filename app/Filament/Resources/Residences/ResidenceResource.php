<?php

namespace App\Filament\Resources\Residences;

use App\Enums\User\RoleType;
use App\Filament\Resources\Residences\Pages\CreateResidence;
use App\Filament\Resources\Residences\Pages\EditResidence;
use App\Filament\Resources\Residences\Pages\ListResidences;
use App\Filament\Resources\Residences\Schemas\ModulesAndFeaturesSubscriptionForm;
use App\Filament\Resources\Residences\Schemas\OperationsDetailsForm;
use App\Filament\Resources\Residences\Schemas\ResidenceAmenitiesForm;
use App\Filament\Resources\Residences\Schemas\ResidenceDetailsForm;
use App\Filament\Resources\Residences\Tables\ResidencesTable;
use App\Models\Residence;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ResidenceResource extends Resource
{
    protected static ?string $model = Residence::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): string
    {
        return __('menu.big_data_management');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Filament::auth()->user()?->hasAnyRole([
            RoleType::SUPER_ADMIN->value,
            RoleType::ADMIN->value,
        ]) ?? false;
    }

    public static function canAccess(): bool
    {
        return static::shouldRegisterNavigation();
    }

    public static function getModelLabel(): string
    {
        return __('menu.residence_summary');
    }

    public static function getPluralModelLabel(): string
    {
        return __('menu.residence_summaries');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('app.residence'))
                    ->columnSpanFull()
                    ->collapsible()
                    ->schema([
                        Wizard::make([
                            Step::make(__('residence.mooban_details'))
                                ->schema(ResidenceDetailsForm::getForm()),
                            Step::make(__('residence.operations'))
                                ->schema(OperationsDetailsForm::getForm()),
                            Step::make(__('residence.modules_features_subscription'))
                                ->schema(ModulesAndFeaturesSubscriptionForm::getForm()),
                            Step::make(__('residence.amenities_or_facilities'))
                                ->schema(ResidenceAmenitiesForm::getForm()),
                        ])
                            ->columns(2)
                            ->skippable(),
                        // ->submitAction(new HtmlString('<button type="submit" class="w-full px-3 py-4 font-medium text-white bg-blue-600 rounded-lg">Submit</button>'))
                    ]),

            ]);
    }

    public static function table(Table $table): Table
    {
        return ResidencesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\GuardPanelAccountsRelationManager::class,
            RelationManagers\GuardTalkAccountsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListResidences::route('/'),
            'create' => CreateResidence::route('/create'),
            'edit' => EditResidence::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with([
            'propertyManagementUser',
            'propertyManagement',
            'subdistrict.district.province',
            'residenceFeatures',
        ]);

        $user = Filament::auth()->user();

        if ($user?->hasRole(RoleType::PROPERTY_MANAGEMENT->value)) {
            return $query->where('property_management_user_id', $user->id);
        }

        if ($user && ! $user->hasAnyRole([RoleType::SUPER_ADMIN->value, RoleType::ADMIN->value])) {
            $query->visibleToUser($user);
        }

        $query->select('residences.*')
            ->leftJoin('residence_stats_view as rsv', 'rsv.residence_id', '=', 'residences.id')
            ->addSelect([
                'rsv.distinct_user_count',
                'rsv.sign_up_percentage',
                'rsv.units_count',
            ]);

        return $query;
    }
}
