<?php

namespace App\Services;

use App\Actions\Residence\GetOneResidenceAction;
use App\Actions\Residence\GetResidenceAction;
use App\Models\Residence;
use App\Support\QueryGuardSupport;

class ResidenceService
{
    public function index($request)
    {
        $getResidenceAction = new GetResidenceAction;
        $getResidenceAction = $getResidenceAction->execute($request);

        return $getResidenceAction;
    }

    public function show(int $id)
    {
        $getOneResidenceAction = new GetOneResidenceAction;
        $getOneResidenceAction = $getOneResidenceAction->execute($id);

        return $getOneResidenceAction;
    }

    /**
     * Search residences for the current user by name, returning [id => label] pairs.
     *
     * @return array<int, string>
     */
    public static function searchResidencesForUser(string $search): array
    {
        $user = auth()->user();

        $query = Residence::query()
            ->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('name_th', 'LIKE', "%{$search}%");
            });

        if ($user->hasRole('Property Management')) {
            $query->where('property_management_user_id', $user->id);
        } elseif ($user->hasRole('Property Management Operation Center')) {
            $residenceIds = array_values(array_filter((array) get_residence_by_property_management_operation_center($user->id)));
            QueryGuardSupport::whereInOrDenyAll($query, 'id', $residenceIds);
        } elseif ($user->hasRole('Re-sales & Tenancy Management')) {
            $query->where('rtm_user_id', $user->id);
        } elseif ($user->hasRole('Sales Management')) {
            $query->where('sales_management_user_id', $user->id);
        }

        return $query
            ->limit(50)
            ->get(['id', 'name', 'name_th'])
            ->mapWithKeys(fn (Residence $r) => [$r->id => "{$r->name} ({$r->name_th})"])
            ->toArray();
    }

    /**
     * Get label for a single residence by ID for the current user.
     */
    public static function getResidenceLabelForUser(int|string|null $value): ?string
    {
        if (! $value) {
            return null;
        }

        $residence = Residence::select('id', 'name', 'name_th')->find($value);

        return $residence ? "{$residence->name} ({$residence->name_th})" : null;
    }
}
