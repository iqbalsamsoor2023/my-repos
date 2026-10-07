<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

// use SimpleSoftwareIO\QrCode\Facades\QrCode;

class VisitorQrController extends Controller
{
    /**
     * Display qr code the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function visitorQR($visitor_code)
    {
        $renderer = new ImageRenderer(
            new RendererStyle(400),
            new SvgImageBackEnd
        );
        $writer = new Writer($renderer);
        $qr_image = $writer->writeString($visitor_code);

        return $qr_image;
    }
}
