<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListSalePropertyRequest;
use App\Http\Requests\StoreSalePropertyRequest;
use App\Http\Resources\SaleAdvertisementResource;
use App\Models\SaleAdvertisement;

class SalePropertyController extends Controller
{
    public function index(ListSalePropertyRequest $request)
    {
        $saleAdvertisements = SaleAdvertisement::where('unit_id', $request->unit_id)->get();

        return success([
            'sale_advertisements' => SaleAdvertisementResource::collection($saleAdvertisements),
        ]);
    }

    public function store(StoreSalePropertyRequest $request)
    {
        $saleAdvertisement = SaleAdvertisement::create([
            'residence_id' => $request->residence_id,
            'unit_id' => $request->unit_id,
            'sale_price' => $request->sale_price,
            'have_ownership_documents' => $request->have_ownshipment_documents,
            'bank_loan_status' => $request->bank_loan_status,
            'is_active' => $request->is_active,
        ]);

        return success([
            'sale_advertisement' => $saleAdvertisement,
        ], 'Sale property created successfully');
    }
}
