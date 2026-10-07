<?php

namespace App\Filament\Pages\PatrolCheckpoints;

use App\Helpers\QrGenerator;
use App\Services\SgocGraphQLService;
use Filament\Pages\Page;

class QrCheckpoint extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-lifebuoy';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.patrol-checkpoints.qr-checkpoint';

    public $checkpointId;

    public function mount($checkpointId)
    {
        $query = <<<'GQL'
                    query(
                        $id: ID!
                        ) {
                        checkpoint(
                            id: $id
                            )  {
                                id
                                name
                            }
                    }
                    GQL;

        $variables['id'] = $checkpointId;
        $response = SgocGraphQLService::execute($query, $variables);

        $data = collect(data_get($response->json(), 'data.checkpoint'));
        $qr_image = QrGenerator::generateQrCode($checkpointId);

        return [
            'checkpoint' => $data,
            'qr_image' => $qr_image,
        ];
    }
}
