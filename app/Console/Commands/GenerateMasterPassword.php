<?php

namespace App\Console\Commands;

use Exception;
use App\Models\MasterPassword;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GenerateMasterPassword extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:masterPassword';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Change master password every 24 Hours';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            do {
                $uniquePassword = Str::random(15);
                $masterPassword = MasterPassword::firstOrNew();
            } while ($uniquePassword === $masterPassword->password);

            $masterPassword->password = $uniquePassword;
            $masterPassword->save();
        } catch (Exception $ex) {
            Log::error('Error while generating/updating master password: '.$ex->getMessage());
        }
    }
}
