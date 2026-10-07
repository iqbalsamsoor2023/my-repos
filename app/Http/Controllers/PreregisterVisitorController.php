<?php

namespace App\Http\Controllers;

use App\Models\PreregisterVisitor;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;

class PreregisterVisitorController extends Controller
{
    public function printQRBulk(Request $request)
    {
        $ids = explode(',', $request->query('ids'));

        foreach ($ids as $id) {
            $preregister_visitor = PreregisterVisitor::with('visitor', 'unit')->find($id);

            $renderer = new ImageRenderer(
                new RendererStyle(500),
                new ImagickImageBackEnd
            );
            $writer = new Writer($renderer);

            $image = base64_encode($writer->writeString($preregister_visitor->visitor_code));

            $data[] = [
                'data' => $preregister_visitor,
                'image' => $image,
            ];
        }

        return view('prebook-qr', compact('data'));
    }
}
