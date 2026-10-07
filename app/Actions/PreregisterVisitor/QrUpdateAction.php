<?php

namespace App\Actions\PreregisterVisitor;

use App\Exceptions\GeneralException;
use App\Models\PreregisterVisitor;
use Illuminate\Http\JsonResponse;

class QrUpdateAction
{
    public function execute(PreregisterVisitor $preregister_visitor)
    {
        $preregister_visitor = $this->update($preregister_visitor);

        return $preregister_visitor;
    }

    private function update(PreregisterVisitor $preregister_visitor)
    {
        $preregister_visitor->update(['is_qr_code_expired' => true]);

        if (! $preregister_visitor) {
            throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Failed updating QR expiry!');
        }

        return $preregister_visitor;
    }
}
