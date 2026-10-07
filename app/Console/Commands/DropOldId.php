<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropOldId extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data-migration:drop-old-id';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Drop old_id column from mysgoc2.companies table';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (Schema::connection('sgoc')->hasColumn('companies', 'old_id')) {
            Schema::connection('sgoc')->table('companies', function (Blueprint $table) {
                $table->dropColumn('old_id');
            });
        }

        return Command::SUCCESS;
    }
}
