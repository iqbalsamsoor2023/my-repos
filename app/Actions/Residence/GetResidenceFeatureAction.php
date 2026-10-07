<?php

namespace App\Actions\Residence;

use App\Models\ResidenceFeature;

class GetResidenceFeatureAction
{
    public function execute($request)
    {
        $residenceFeatures = ResidenceFeature::query();

        if (isset($request->residence_id)) {
            $residenceFeatures = $residenceFeatures->where('residence_id', $request->residence_id);
        }

        if (isset($request->feature_id)) {
            $residenceFeatures = $residenceFeatures->where('feature_id', $request->feature_id);
        }

        return $residenceFeatures->paginate(25);
    }
}
