<?php

namespace App\Filament\Resources\PrebookVisitors\Pages;

use App\Actions\Visitor\GenerateVisitorQRAction;
use App\Filament\Resources\PrebookVisitors\PrebookVisitorResource;
use App\Models\PreregisterVisitor;
use Filament\Resources\Pages\Page;

class QrPrebookVisitor extends Page
{
    protected static string $resource = PrebookVisitorResource::class;

    protected string $view = 'filament.resources.prebook-visitors.pages.qr-prebook-visitor';

    public $preregisterVisitorId;

    public function mount($preregisterVisitorId)
    {
        $generateQr = new GenerateVisitorQRAction;
        $prebook_visitor = PreregisterVisitor::findOrFail($preregisterVisitorId);

        $qr_image = $generateQr->execute($prebook_visitor->visitor_code);

        return [
            'prebook_visitor' => $prebook_visitor,
            'qr_image' => $qr_image,
        ];
    }
}
