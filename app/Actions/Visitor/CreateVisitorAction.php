<?php

namespace App\Actions\Visitor;

use App\Exceptions\GeneralException;
use App\Models\Visitor;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CreateVisitorAction
{
    public function execute($request)
    {
        $visitor = $this->createVisitor($request);

        return $visitor;
    }

    public function createVisitor($request)
    {
        $name = data_get($request, 'name', '-');
        $contactNo = data_get($request, 'contact_no', '-');
        $idType = data_get($request, 'id_type', 0);
        $idNumber = data_get($request, 'id_number', '-');

        $data = [
            'name' => $name,
            'contact_no' => $contactNo,
            'id_type' => $idType,
            'id_number' => $idNumber,
        ];

        $visitor = Visitor::firstOrCreate($data, $data);

        if (! $visitor) {
            DB::rollBack();
            throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Failed creating visitor');
        }

        return $visitor;
    }
}
