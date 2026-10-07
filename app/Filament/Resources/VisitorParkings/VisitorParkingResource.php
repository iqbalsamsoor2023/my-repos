<?php

namespace App\Filament\Resources\VisitorParkings;

use App\Filament\Resources\VisitorParkings\Pages\ListVisitorParkings;
use App\Filament\Resources\VisitorParkings\Tables\VisitorParkingsTable;
use App\Forms\Components\VisitorParking\ParkingRate;
use App\Models\VisitorParking;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VisitorParkingResource extends Resource
{
    protected static ?string $model = VisitorParking::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): string
    {
        return __('menu.security_management');
    }

    public static function getModelLabel(): string
    {
        return __('Visitor Parking');
    }

    public static function getPluralModelLabel(): string
    {
        return __('menu.securityManagement.manage_visitor_parkings');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('visitor.parking_rate'))
                    ->schema([
                        ParkingRate::make(''),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return VisitorParkingsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVisitorParkings::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        $query = VisitorParking::with([
            'visitorLog.visitingArrangements',
            'visitorLog.visitingArrangements.unit',
            'calculation.parking',
            'visitorLog.visitingArrangements.unit.residence',
        ])
            ->whereHas('visitorLog');

        if ($user->hasRole('Property Management')) {
            $query->whereHas('visitorLog.visitingArrangements.unit.residence', function ($q) use ($user) {
                $q->where('property_management_user_id', $user->id);
            });
        } elseif ($user->hasRole('Property Management Operation Center')) {
            $residence_ids = get_residence_by_property_management_operation_center($user->id);

            $query->whereHas('visitorLog.visitingArrangements', function ($subQuery) use ($residence_ids) {
                $subQuery->whereIn('residence_id', $residence_ids);
            });
        }

        return $query;
    }
}
