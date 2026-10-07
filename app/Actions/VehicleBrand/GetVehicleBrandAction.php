<?php

namespace App\Actions\VehicleBrand;

use App\Models\VehicleBrand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class GetVehicleBrandAction
{
    public function execute(Request $request)
    {
        $filters = [
            'gpl_priority' => $request->filled('gpl_priority'),
            'ids'          => $request->ids ? implode(',', $request->ids) : null,
            'type'         => $request->type,
            'paginate'     => $request->boolean('has_pagination'),
            'page'         => $request->get('page', 1),
        ];

        $cacheKey = 'vehicle_brands:' . md5(json_encode($filters));

        return Cache::tags(['vehicle_brands'])->rememberForever($cacheKey, function () use ($request) {

            $query = VehicleBrand::query()
                ->with(['media', 'vehicleModels'])
                ->when(
                    $request->filled('gpl_priority'),
                    fn ($q) => $q->whereNull('gpl_priority')
                )
                ->when(
                    $request->filled('ids'),
                    fn ($q) => $q->whereIn('id', $request->ids)
                )
                ->when(
                    $request->filled('type'),
                    fn ($q) =>
                        $q->whereHas(
                            'vehicleModels',
                            fn ($q2) => $q2->where('type', $request->type)
                        )
                )
                ->orderBy('gpl_priority');

         
                if (isset($request->has_pagination) && ($request->has_pagination == false)) {
                    return $query->get();
                }
        
                return $query->orderBy('gpl_priority')->paginate(100);
        });
    }
}
