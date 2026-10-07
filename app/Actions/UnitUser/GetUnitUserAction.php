<?php

namespace App\Actions\UnitUser;

use App\Models\UnitUser;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class GetUnitUserAction
{
    private const PAGINATION_LIMIT = 20;

    public function execute(Request $request): LengthAwarePaginator|Collection
    {
        $unitUsers = UnitUser::with([
            'unit',
            'unit.residence',
            'unit.residence.subdistrict',
            'unit.residence.subdistrict.district',
            'unit.residence.subdistrict.district.province',
            'unit.residence.developer',
            'unit.residence.propertyManagementUser',
            'unit.residence.residenceGuardUser',
            'user',
            'user.roles',
        ]);

        $this->applyFilters($unitUsers, $request);

        $unitUsers = $unitUsers->orderByDesc('id')->paginate(self::PAGINATION_LIMIT);

        return $unitUsers;
    }

    private function applyFilters($unitUsers, Request $request): void
    {
        $unitUsers->when($request->has('is_owner'), function ($query) use ($request) {
            return $query->where('is_owner', $request->is_owner);
        })
            ->when($request->has('unit_id'), function ($query) use ($request) {
                return $query->where('unit_id', $request->unit_id);
            })
            ->when($request->has('user_id'), function ($query) use ($request) {
                return $query->where('user_id', $request->user_id);
            })
            ->when($request->has('residence_id'), function ($query) use ($request) {
                return $query->whereHas('unit', function ($q) use ($request) {
                    $q->where('residence_id', $request->residence_id);
                });
            })
            ->when($request->has('district_id'), function ($query) use ($request) {
                return $query->whereHas('unit.residence.subdistrict', function ($q) use ($request) {
                    $q->where('district_id', $request->district_id);
                });
            })
            ->when($request->has('unit_number'), function ($query) use ($request) {
                return $query->whereHas('unit', function ($q) use ($request) {
                    $q->where('unit_number', 'LIKE', '%'.$request->unit_number.'%');
                });
            })
            ->when($request->has('email'), function ($query) use ($request) {
                return $query->whereHas('user', function ($q) use ($request) {
                    $q->where('email', 'LIKE', '%'.$request->email.'%');
                });
            })
            ->when($request->has('name'), function ($query) use ($request) {
                return $query->whereHas('user', function ($q) use ($request) {
                    $q->where('name', 'LIKE', '%'.$request->name.'%');
                });
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                return $query->where(function ($searchQuery) use ($request) {
                    $searchQuery->whereHas('user', function ($q) use ($request) {
                        $q->where('name', 'LIKE', '%'.$request->search.'%')
                            ->orWhere('email', 'LIKE', '%'.$request->search.'%');
                    })->orWhereHas('unit', function ($q) use ($request) {
                        $q->where('unit_number', 'LIKE', '%'.$request->search.'%');
                    });
                });
            });
    }
}
