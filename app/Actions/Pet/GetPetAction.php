<?php

namespace App\Actions\Pet;

use App\Models\Pet;
use Illuminate\Http\Request;

class GetPetAction
{
    public function execute(Request $request)
    {
        $pet = Pet::with('user', 'unit');

        if (isset($request->unit_id)) {
            $pet = $pet->where('unit_id', $request->unit_id);
        }

        if (isset($request->user_id)) {
            $pet = $pet->where('user_id', $request->user_id);
        }

        return $pet->orderBy('id', 'DESC')->paginate(20);
    }
}
