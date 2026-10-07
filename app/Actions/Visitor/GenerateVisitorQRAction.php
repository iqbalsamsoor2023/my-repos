<?php

namespace App\Actions\Visitor;

use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class GenerateVisitorQRAction
{
    public function execute(string $visitor_code)
    {
        $qr_code = $this->generateVisitorQR($visitor_code);

        return $qr_code;
    }

    public function generateVisitorQR(string $visitor_code)
    {
        $renderer = new ImageRenderer(
            new RendererStyle(500),
            new ImagickImageBackEnd
        );
        $writer = new Writer($renderer);

        return base64_encode($writer->writeString($visitor_code));
    }
}
