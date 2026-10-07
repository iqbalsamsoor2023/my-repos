<?php

namespace App\Interfaces;

use App\Http\Requests\UnitTenant\StoreUnitTenantRequest;
use App\Http\Requests\UnitTenant\UpdateUnitTenantRequest;

interface UnitTenantRepositoryInterface
{
    public function create(StoreUnitTenantRequest $request);

    public function update(UpdateUnitTenantRequest $request, int $id);

    public function delete(int $id);
}
