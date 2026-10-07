<?php

namespace App\Filament\Resources\Committees;

use App\Filament\Resources\Committees\Pages\CreateCommittee;
use App\Filament\Resources\Committees\Pages\EditCommittee;
use App\Filament\Resources\Committees\Pages\ListCommittees;
use App\Filament\Resources\Committees\Schemas\CommitteeForm;
use App\Filament\Resources\Committees\Tables\CommitteesTable;
use App\Models\Committee;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CommitteeResource extends Resource
{
    protected static ?string $model = Committee::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): string
    {
        return __('menu.asset_management');
    }

    public static function getModelLabel(): string
    {
        return __('committee.committee');
    }

    public static function getPluralModelLabel(): string
    {
        return __('committee.committees');
    }

    public static function getNavigationLabel(): string
    {
        return __('menu.assetManagement.manage_committees');
    }

    public static function form(Schema $schema): Schema
    {
        return CommitteeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CommitteesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCommittees::route('/'),
            'create' => CreateCommittee::route('/create'),
            'edit' => EditCommittee::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $query = parent::getEloquentQuery()
            ->with([
                'user:id,name,email',
                'unit:id,unit_number',
                'residence:id,name',
            ]);

        if ($user->hasRole('Property Management')) {
            $query->whereHas('residence', fn ($q) => $q->where('property_management_user_id', $user->id));
        } elseif ($user->hasRole('Property Management Operation Center')) {
            $residenceIds = get_residence_by_property_management_operation_center($user->id);
            $query->whereIn('residence_id', $residenceIds);
        } elseif ($user->hasRole('Re-sales & Tenancy Management')) {
            $residenceIds = get_residence_id_list_by_rtm($user->id);
            $query->whereIn('residence_id', $residenceIds);
        } elseif ($user->hasRole('Sales Management')) {
            $residenceIdList = get_residence_id_list_by_sm($user->id);
            $query->whereIn('residence_id', $residenceIdList);
        }

        return $query;
    }
}
