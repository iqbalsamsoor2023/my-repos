<?php

namespace App\Interfaces;

use App\Http\Requests\FacilityBooking\StoreFacilityBookingRequest;
use App\Http\Requests\FacilityBooking\UpdateFacilityBookingRequest;
use Illuminate\Http\Request;

interface FacilityBookingRepositoryInterface
{
    public function index(Request $request);

    public function create(StoreFacilityBookingRequest $request);

    public function update(UpdateFacilityBookingRequest $request, int $id);
}
