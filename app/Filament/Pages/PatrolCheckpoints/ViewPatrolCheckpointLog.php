<?php

namespace App\Filament\Pages\PatrolCheckpoints;

use Filament\Pages\Page;

class ViewPatrolCheckpointLog extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-lifebuoy';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'patrol-checkpoint';

    protected string $view = 'filament.pages.patrol-checkpoints.view-patrol-checkpoint-log';
}
