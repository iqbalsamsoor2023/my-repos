<?php

namespace App\Actions\ResourceMaterial;

use App\Models\Erp\StaticContent;

class GetOneStaticContentAction
{
    public function execute($request)
    {
        $staticContent = StaticContent::query();

        if (isset($request->type)) {
            $staticContent = $staticContent->where('type', $request->type);
        }

        return $staticContent->first();
    }
}
