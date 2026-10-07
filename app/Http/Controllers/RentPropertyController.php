<?php

namespace App\Http\Controllers;

use Exception;
use Throwable;
use App\Http\Requests\CreateRentPropertyRequest;
use App\Http\Requests\ListRentPropertyRequest;
use App\Http\Resources\RentAdvertisementResource;
use App\Models\RentAdvertisement;
use App\Models\RentalRule;
use Illuminate\Support\Facades\DB;

class RentPropertyController extends Controller
{
    public function index(ListRentPropertyRequest $request)
    {
        $rentAdvertisements = RentAdvertisement::with('rent_rules')->where('unit_id', $request->unit_id)->get();

        return success([
            'rent_advertisements' => RentAdvertisementResource::collection($rentAdvertisements),
        ]);
    }

    public function store(CreateRentPropertyRequest $request)
    {
        try {
            DB::beginTransaction();
            $existingRentAdvertisement = RentAdvertisement::where('unit_id', $request->unit_id)->first();
            if($existingRentAdvertisement) {
                throw new Exception('Rent property already exists for this unit');
            }
            $rentAdvertisement = RentAdvertisement::create([
                'residence_id' => $request->residence_id,
                'unit_id' => $request->unit_id,
                'rent_price' => $request->rent_price,
                'rental_start_date' => $request->rental_start_date,
                'contract_months' => $request->contract_months,
                'deposit' => $request->deposit,
                'has_custom_rule' => $request->has_custom_rule,
                'is_active' => $request->is_active,
            ]);

            if ($request->has_custom_rule) {
                foreach ($request->custom_rules as $customRule) {
                    RentalRule::create([
                        'unit_id' => $request->unit_id,
                        'deposit' => $customRule['deposit'],
                        'contract_months' => $customRule['contract_months'],
                        'price_per_month' => $customRule['price_per_month'],
                    ]);
                }
            }

            DB::commit();

            return success([
                'rent_advertisement' => $rentAdvertisement,
            ], 'Rent property created successfully');
        } catch (Throwable $th) {
            DB::rollBack();

            return error($th->getMessage());
        }
    }
}
