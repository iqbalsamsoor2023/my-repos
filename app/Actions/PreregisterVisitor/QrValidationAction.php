<?php

namespace App\Actions\PreregisterVisitor;

use App\Exceptions\GeneralException;
use App\Models\PreregisterVisitor;
use Illuminate\Http\JsonResponse;

class QrValidationAction
{
    public function execute(PreregisterVisitor $preregister_visitor)
    {
        $preregister_visitor = $this->qrValidation($preregister_visitor);

        return $preregister_visitor;
    }

    private function qrValidation(PreregisterVisitor $preregister_visitor)
    {
        if ($preregister_visitor->is_qr_code_expired == true) {
            throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Expire QR!');
        }

        if (now()->format('Y-m-d') < date('Y-m-d', strtotime($preregister_visitor->validity_start_date))) {
            throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Invalid QR! Your QR will valid start from '.date('Y-m-d', strtotime($preregister_visitor->validity_start_date)));
        }
    }
}
