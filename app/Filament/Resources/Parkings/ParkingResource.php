<?php

namespace App\Filament\Resources\Parkings;

use App\Filament\Resources\Parkings\Pages\CreateParking;
use App\Filament\Resources\Parkings\Pages\EditParking;
use App\Filament\Resources\Parkings\Pages\ListParkings;
use App\Filament\Resources\Parkings\RelationManagers\CalculationsRelationManager;
use App\Filament\Resources\Parkings\Schemas\ParkingForm;
use App\Filament\Resources\Parkings\Tables\ParkingsTable;
use App\Models\Parking;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ParkingResource extends Resource
{
    protected static ?string $model = Parking::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): string
    {
        return __('menu.security_management');
    }

    public static function getModelLabel(): string
    {
        return __('Parking');
    }

    public static function getPluralModelLabel(): string
    {
        return __('menu.securityManagement.manage_parking_fees_basic');
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return ParkingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ParkingsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            CalculationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListParkings::route('/'),
            'create' => CreateParking::route('/create'),
            'edit' => EditParking::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = Parking::with('residence');
        $user = auth()->user();

        $query = parent::getEloquentQuery()
            ->select([
                'id',
                'residence_id',
                'type',
                'rate_mode',
                'is_discount_coupon',
                'discount_type',
                'created_at',
            ])
            ->with([
                'residence:id,name,name_th',
            ]);

        if ($user->hasRole('Property Management')) {
            $query->whereHas('residence', function ($q) use ($user) {
                $q->where('property_management_user_id', $user->id);
            });
        } elseif ($user->hasRole('Property Management Operation Center')) {
            $residence_ids = get_residence_by_property_management_operation_center($user->id);

            $query->whereIn('residence_id', $residence_ids);
        }

        return $query;
    }
}
