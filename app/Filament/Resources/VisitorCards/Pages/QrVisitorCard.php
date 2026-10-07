<?php

namespace App\Filament\Resources\VisitorCards\Pages;

use App\Actions\Visitor\GenerateVisitorQRAction;
use App\Filament\Resources\VisitorCards\VisitorCardResource;
use App\Models\VisitorCard;
use Filament\Resources\Pages\Page;

class QrVisitorCard extends Page
{
    protected static string $resource = VisitorCardResource::class;

    protected string $view = 'filament.resources.visitor-card-resource.pages.qr-visitor-card';

    public $visitorCardId;

    public function mount($visitorCardId)
    {
        $generateQr = new GenerateVisitorQRAction;
        $visitor_card = VisitorCard::findOrFail($visitorCardId);
        $qr_image = $generateQr->execute($visitorCardId);

        return [
            'visitor_card' => $visitor_card,
            'qr_image' => $qr_image,
        ];
    }
}
