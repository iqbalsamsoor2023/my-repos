<?php

namespace App\Filament\Resources\Maintenances\Pages;

use App\Models\ResidenceAmenity;
use App\Models\ResidenceAmenityOption;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use App\Enums\Maintenance\MaintenanceStatus;
use App\Filament\Resources\Maintenances\MaintenanceResource;
use App\Models\Maintenance;
use App\Models\PrivateClaimItem;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListMaintenances extends ListRecords
{
    protected static string $resource = MaintenanceResource::class;

    protected function getTableQuery(): Builder
    {
        $query = Maintenance::query()
            ->with(['reportedBy', 'privateClaimCategory', 'privateClaimItem', 'privateClaimItemTitle'])
            ->with([
                'maintainable' => function ($morphTo) {
                    $morphTo->morphWith([
                        ResidenceAmenity::class => ['residence'],
                        ResidenceAmenityOption::class => ['residenceAmenity.residence'],
                    ]);
                },
            ]);

        $user = auth()->user();

        if ($user->hasRole('Property Management')) {
            $residence = get_residence_by_property_management($user->id);

            $query->where(function ($q) use ($residence) {
                $q->whereHasMorph('maintainable', [ResidenceAmenity::class], function ($q) use ($residence) {
                    $q->where('residence_id', $residence->id);
                })->orWhereHasMorph('maintainable', [ResidenceAmenityOption::class], function ($q) use ($residence) {
                    $q->whereHas('residenceAmenity', function ($q) use ($residence) {
                        $q->where('residence_id', $residence->id);
                    });
                });
            });
        }

        return $query;
    }

    protected function getTableFilters(): array
    {
        return [
            SelectFilter::make('status')
                ->options(collect(MaintenanceStatus::cases())
                    ->mapWithKeys(fn ($status) => [$status->value => $status->label()])
                    ->toArray()
                ),
            TernaryFilter::make('is_verified'),
            Filter::make('created_at')
                ->schema([
                    DatePicker::make('report_from')->default(null),
                    DatePicker::make('report_until')->default(null),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            $data['report_from'],
                            fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                        )
                        ->when(
                            $data['report_until'],
                            fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                        );
                }),
        ];
    }
}
