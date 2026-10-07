<?php

namespace App\Console\Commands;

use App\Models\Residence;
use App\Models\User;
use Illuminate\Console\Command;

class DeleteResidences extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'residences:delete';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete residences by specific IDs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $ids = [
            397, 2323, 1952, 3696, 3698, 3697, 3589, 200, 1162, 3362, 1969, 639, 4201, 3736, 58, 5159, 3390, 829,
            3482, 5489, 2523, 3168, 3105, 1214, 1651, 1652, 1653, 28, 2191, 3294, 2320, 5306, 2431, 1709, 760, 765,
            851, 1705, 3580, 3671, 2767, 2224, 3220, 5396, 2377, 2405, 5005, 2024, 3090, 663, 2924, 513, 1030, 73,
            2633, 2919, 5422, 220, 4205, 3365, 3269, 3222, 4193, 182, 2450, 2452, 2453, 3636, 2341, 2446, 2294, 2295,
            5126, 2849, 5462, 2316, 2317, 1, 2386, 2678, 2657, 5279, 2165, 5655, 5089, 3641, 2888, 3086, 2586, 2355,
            5307, 2561, 2469, 2811, 2742, 2051, 1222, 3486, 3503, 3705, 2541, 5519, 3786, 5152, 1807, 2076, 3666, 885,
            2718, 1614, 823, 3293, 3034, 121, 1319, 2374, 737, 2128, 2543, 2553, 1482, 3968, 2153,
        ];

        $totalToDelete = Residence::whereIn('id', $ids)->count();

        if ($totalToDelete === 0) {
            $this->info('No residences found for deletion.');

            return;
        }

        $this->info("Deleting {$totalToDelete} residences...");

        $progressBar = $this->output->createProgressBar($totalToDelete);
        $progressBar->start();

        Residence::whereIn('id', $ids)->chunkById(10, function ($residences) use ($progressBar) {
            foreach ($residences as $residence) {
                User::where('id', $residence->property_management_user_id)->delete();
                $residence->delete();
                $progressBar->advance();
            }
        });

        $progressBar->finish();
        $this->info("\nDeletion complete.");
    }
}
