<?php

namespace App\Actions\ResourceMaterial;

use App\Models\Erp\ResourceMaterial;
use Illuminate\Http\Request;

class GetResourceMaterialAction
{
    public function execute(Request $request)
    {
        $resourceMaterials = ResourceMaterial::query();

        if (isset($request->platform_id)) {
            $resourceMaterials = $resourceMaterials->where('platform_id', $request->platform_id);
        }

        if (isset($request->type)) {
            $resourceMaterials = $resourceMaterials->where('type', $request->type);
        }

        return $resourceMaterials->paginate(20);
    }
}
