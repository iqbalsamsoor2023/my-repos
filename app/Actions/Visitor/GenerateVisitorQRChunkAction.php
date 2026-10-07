<?php

namespace App\Actions\Visitor;

use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Storage;

class GenerateVisitorQRChunkAction
{
    public function execute(string $visitorCode): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle(500),
            new ImagickImageBackEnd()
        );

        $writer = new Writer($renderer);

        $png = $writer->writeString($visitorCode);

        $filename = 'visitor-cards/qr/' .
            hash('sha256', $visitorCode) .
            '.png';

        Storage::disk('public')->put(
            $filename,
            $png
        );

        return $filename;
    }
}
