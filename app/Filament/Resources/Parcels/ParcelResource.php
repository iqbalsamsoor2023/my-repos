<?php

namespace App\Filament\Resources\Parcels;

use App\Filament\Resources\Parcels\Pages\CreateParcel;
use App\Filament\Resources\Parcels\Pages\EditParcel;
use App\Filament\Resources\Parcels\Pages\ListParcels;
use App\Filament\Resources\Parcels\Schemas\ParcelForm;
use App\Filament\Resources\Parcels\Tables\ParcelsTable;
use App\Models\Parcel;
use App\Policies\ParcelPolicy;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ParcelResource extends Resource
{
    protected static ?string $model = Parcel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): string
    {
        return __('menu.operation_management');
    }

    public static function getModelLabel(): string
    {
        return __('menu.parcels');
    }

    public static function form(Schema $schema): Schema
    {
        return ParcelForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ParcelsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListParcels::route('/'),
            'create' => CreateParcel::route('/create'),
            'edit' => EditParcel::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->select([
                'parcels.id',
                'parcels.unit_id',
                'parcels.parcel_generated_no',
                'parcels.receiver_id',
                'parcels.receiver_name',
                'parcels.pickup_person_contact_no',
                'parcels.pickup_person_name',
                'parcels.qr_code',
                'parcels.description',
                'parcels.courier_id',
                'parcels.tracking_no',
                'parcels.status',
                'parcels.pickup_type',
                'parcels.created_at',
                'parcels.updated_at',
                'parcels.pickup_time',
            ])
            ->with([
                'unit:id,unit_number,residence_id,deleted_at',
                'unit.residence:id,name,name_th,deleted_at',
                'courier:id,name,deleted_at',
            ])
            ->whereHas('unit', fn ($q) => $q->whereNull('deleted_at'))
            ->whereHas('unit.residence', fn ($q) => $q->whereNull('deleted_at'))
            ->whereHas('courier', fn ($q) => $q->whereNull('deleted_at'));

        $user = Auth::user();

        if (ParcelPolicy::isPropertyManager($user)) {
            $query->whereHas('unit.residence', fn ($q) => $q->where('property_management_user_id', $user->id));
        } elseif (ParcelPolicy::isOperationCenter($user)) {
            $residenceIds = get_residence_by_property_management_operation_center($user->id);
            $query->whereHas('unit', fn ($q) => $q->whereIn('residence_id', $residenceIds));
        }

        return $query;
    }
}
