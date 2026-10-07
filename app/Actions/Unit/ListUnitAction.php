<?php

namespace App\Actions\Unit;

use App\Exceptions\GeneralException;
use App\Http\Requests\GetUnitRequest;
use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class ListUnitAction
{
    public function execute(GetUnitRequest $request)
{
    $query = Unit::query()
        ->select('id', 'residence_id', 'unit_number', 'street', 'floor', 'block')
        ->with(['unitUsers.user' => function ($query) {
            $query->select(
                'id',
                'country_id',
                'name',
                'email',
                'email_verified_at',
                'pdpa_agreed_at',
                'id_number',
                'phone_no',
                'address',
                'is_community_head_verified',
                'date_of_birth',
                'gender',
                'passport_number',
                'passport_expiry',
                'created_at',
                'updated_at',
                'deleted_at'
            );
        }]);

    // ========================
    // APPLY FILTERS
    // ========================

    if ($request->filled('residence_id')) {
        $query->where('residence_id', $request->residence_id);
    }

    if ($request->filled('unit_number')) {
        $query->where('unit_number', 'like', "%{$request->unit_number}%");
    }

    if ($request->filled('name')) {
        $query->whereHas('unitUsers.user', function ($subQuery) use ($request) {
            $subQuery->where('name', 'like', "%{$request->name}%");
        });
    }

    if ($request->filled('unit_user_id')) {
        $query->whereHas('unitUsers', function ($subQuery) use ($request) {
            $subQuery->where('id', $request->unit_user_id);
        });
    }

    if ($request->filled('user_id')) {
        $query->whereHas('unitUsers', function ($subQuery) use ($request) {
            $subQuery->where('user_id', $request->user_id);
        });
    }

    // ========================
    // DETECT SEARCH MODE
    // ========================

    $isSearch =
        $request->filled('unit_number') ||
        $request->filled('name') ||
        $request->filled('unit_user_id') ||
        $request->filled('user_id');

    // ========================
    // CACHE ONLY IF NOT SEARCH
    // ========================

    if (!$isSearch) {

        $page = $request->get('page', 1);
        $residenceId = $request->residence_id ?? 'all';

        $cacheKey = "units:list:residence:$residenceId:page:$page";

        $units = Cache::tags(["units:residence:$residenceId"])
            ->remember(
                $cacheKey,
                now()->addMinutes(10),   // <-- IMPORTANT: short cache only
                fn () => $query->paginate(20)
            );

    } else {

        // SEARCH → NEVER CACHE
        $units = $query->paginate(20);
    }

    // ========================
    // FORMAT RESULT
    // ========================

    $units->getCollection()->transform(function ($unit) {

        return [
            'id' => $unit->id,
            'residence_id' => $unit->residence_id,
            'unit_number' => $unit->unit_number,
            'street' => $unit->street,
            'floor' => $unit->floor,
            'block' => $unit->block,
            'unit_users' => $unit->unitUsers
                ->filter(fn ($u) => $u->user)
                ->map(fn ($u) => [
                    'unit_id' => $u->unit_id,
                    'user_id' => $u->user_id,
                    'user' => [
                        'id' => $u->user->id,
                        'country_id' => $u->user->country_id,
                        'name' => $u->user->name,
                        'email' => $u->user->email,
                        'email_verified_at' => optional($u->user->email_verified_at)?->format('Y-m-d H:i:s'),
                        'pdpa_agreed_at' => $u->user->pdpa_agreed_at,
                        'id_number' => $u->user->id_number,
                        'phone_no' => $u->user->phone_no,
                        'address' => $u->user->address,
                        'is_community_head_verified' => $u->user->is_community_head_verified,
                        'date_of_birth' => optional($u->user->date_of_birth)?->format('Y-m-d H:i:s'),
                        'gender' => $u->user->gender,
                        'passport_number' => $u->user->passport_number,
                        'passport_expiry' => $u->user->passport_expiry,
                        'created_at' => $u->user->created_at->format('Y-m-d H:i:s'),
                        'updated_at' => $u->user->updated_at->format('Y-m-d H:i:s'),
                    ],
                ])
                ->values()
                ->all(),
        ];
    });

    if ($units->isEmpty()) {
        throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Failed to retrieve units');
    }

    return $units;
}
}
