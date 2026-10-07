<?php

namespace App\Filament\Widgets\Default;

use App\Models\UnitUser;
use Filament\Widgets\ChartWidget;

class OwnersVsTenantsChart extends ChartWidget
{
    public ?string $filter = 'today';

    protected int|string|array $columnSpan = 1;

    protected ?string $pollingInterval = null;

    public function getHeading(): string
    {
        return __('app.owner_vs_tenant');
    }

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getData(): array
    {
        $user = auth()->user();

        $query = UnitUser::query()
            ->join('units', 'unit_user.unit_id', '=', 'units.id')
            ->whereNull('unit_user.deleted_at')
            ->whereNull('units.deleted_at');

        if ($user->hasRole('Property Management')) {
            $query->whereIn('units.residence_id', function ($q) use ($user) {
                $q->select('id')
                    ->from('residences')
                    ->where('property_management_user_id', $user->id);
            });
        } elseif ($user->hasRole('Property Management Operation Center')) {
            $residence_ids = get_residence_by_property_management_operation_center($user->id);
            $query->whereIn('units.residence_id', $residence_ids);
        }

        // Group by owner/tenant and count
        $counts = $query
            ->selectRaw('is_owner, COUNT(*) as total')
            ->groupBy('is_owner')
            ->pluck('total', 'is_owner')
            ->toArray();

        $owner = $counts[1] ?? 0;
        $tenant = $counts[0] ?? 0;

        return [
            'datasets' => [
                [
                    'label' => 'Owners VS Tenants',
                    'data' => [$owner, $tenant],
                    'backgroundColor' => [
                        'rgb(54, 162, 235)',
                        'rgb(75, 192, 192)',
                    ],
                    'borderColor' => [
                        'rgb(54, 162, 235)',
                        'rgb(75, 192, 192)',
                    ],
                ],
            ],

            'labels' => [__('Owner'), __('Tenant')],
        ];
    }
}