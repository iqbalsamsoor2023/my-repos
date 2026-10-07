<?php

namespace App\Console\Commands\ImageMigrations;

use App\Jobs\ImageMigrations\CopyResidenceImageJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResidencesImage
{
    /**
     * Execute the console command.
     *
     * @return int
     */
    public function execute($residence_id)
    {
        $mmb1_residences = DB::connection('mmb1')
            ->table('residences')
            ->get();

        foreach ($mmb1_residences as $mmb1_residence) {
            CopyResidenceImageJob::dispatch($mmb1_residence);
        }

        return true;
    }
}
