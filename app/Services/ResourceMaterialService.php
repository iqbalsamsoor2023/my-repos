<?php

namespace App\Services;

use App\Actions\ResourceMaterial\GetResourceMaterialAction;
use Illuminate\Http\Request;

class ResourceMaterialService
{
    public function index(Request $request)
    {
        $getResourceMaterialAction = new GetResourceMaterialAction;
        $resourceMaterial = $getResourceMaterialAction->execute($request);

        return $resourceMaterial;
    }
}
