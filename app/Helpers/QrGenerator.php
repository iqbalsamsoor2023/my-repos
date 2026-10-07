<?php

namespace App\Helpers;

use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class QrGenerator
{
    public static function generateQrCode(string $code)
    {
        $renderer = new ImageRenderer(
            new RendererStyle(500),
            new ImagickImageBackEnd
        );
        $writer = new Writer($renderer);

        return base64_encode($writer->writeString($code));
    }
}
