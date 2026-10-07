<?php

namespace App\Console\Commands\Visitor;

use App\Models\PreregisterVisitor;
use Carbon\Carbon;
use DateInterval;
use Illuminate\Console\Command;

class AutoExpiredPreRegisterQR extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'visitor:update-preregister-visitor-qr';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update pre-register qr validity to expired';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $active_qr_visitors = PreregisterVisitor::select('id', 'is_multiple_entry', 'validity_start_date', 'validity_end_date', 'is_qr_code_expired')->where('is_qr_code_expired', 0)->get();
        $bar = $this->output->createProgressBar($active_qr_visitors->count());
        $bar->start();

        foreach ($active_qr_visitors as $visitor) {
            if ($visitor->is_multiple_entry == 1) {
                if ($visitor->validity_end_date < now()->format('Y-m-d')) {
                    $visitor->update([
                        'is_qr_code_expired' => true,
                    ]);
                }
            } else {
                $validity_start_date = $visitor->validity_start_date;
                $plus_48_hours = Carbon::parse($validity_start_date)->add(new DateInterval('P2D'));
                if (now()->format('Y-m-d') > $plus_48_hours->format('Y-m-d')) {
                    PreregisterVisitor::where('id', $visitor->id)->update([
                        'is_qr_code_expired' => true,
                    ]);
                }
            }
        }

        $bar->finish();

        return static::SUCCESS;
    }
}
