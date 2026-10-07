<?php

namespace App\Console\Commands;

use App\Models\LogisticPartner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateLogisticPartnerPriority extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-logistic-partner-priority';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Logistic Partner priority separately for food delivery and courier';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        DB::beginTransaction();

        try {
            // IDs to force last
            $forceLastIds = [110, 124]; // Others option for F&B and Courier

            // FOOD DELIVERY PRIORITY
            $this->info('Updating Food Delivery priority...');

            $foodCounts = DB::table('visitor_logs')
                ->select('food_delivery_logistic_partner_id as logistic_partner_id', DB::raw('COUNT(*) as total'))
                ->whereNotNull('food_delivery_logistic_partner_id')
                ->groupBy('food_delivery_logistic_partner_id')
                ->orderByDesc('total')
                ->get();

            $priority = 1;

            foreach ($foodCounts as $row) {
                if (in_array($row->logistic_partner_id, $forceLastIds)) {
                    continue; // skip 110 & 124 for now
                }

                LogisticPartner::where('id', $row->logistic_partner_id)
                    ->update(['most_usage' => $priority]);

                $priority++;
            }

            // Force 110 & 124 as last
            foreach ($forceLastIds as $id) {
                LogisticPartner::where('id', $id)->update(['most_usage' => $priority]);
                $priority++;
            }

            // COURIER PRIORITY
            $this->info('Updating Courier priority...');

            $courierCounts = DB::table('visitor_logs')
                ->select('courier_logistic_partner_id as logistic_partner_id', DB::raw('COUNT(*) as total'))
                ->whereNotNull('courier_logistic_partner_id')
                ->groupBy('courier_logistic_partner_id')
                ->orderByDesc('total')
                ->get();

            $priority = 1;

            foreach ($courierCounts as $row) {
                if (in_array($row->logistic_partner_id, $forceLastIds)) {
                    continue; // skip 110 & 124 for now
                }

                LogisticPartner::where('id', $row->logistic_partner_id)
                    ->update(['most_usage' => $priority]);

                $priority++;
            }

            // Force 110 & 124 as last
            foreach ($forceLastIds as $id) {
                LogisticPartner::where('id', $id)->update(['most_usage' => $priority]);
                $priority++;
            }

            DB::commit();

            $this->info('Both Food Delivery & Courier priorities updated successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Failed: ' . $e->getMessage());
        }
    }
}
