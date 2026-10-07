<?php

namespace App\Console\Commands\DataMigrations;

use App\Models\VisitorCard;
use Exception;
use Illuminate\Support\Facades\DB;

class Register3rdPartyVisitorCardData
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:name';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Register 3rd party visitor qr';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function execute($residence_id)
    {
        try {
            DB::beginTransaction();
            $mmb1Data = DB::connection('mmb1')
                ->table('visitors')
                ->where('visitors.residence_id', $residence_id)
                ->orderBy('visitors.id')
                ->chunk(300, function ($datas, $unit) use ($residence_id) {
                    foreach ($datas as $key => $value) {
                        $mmb2_visitor_card = VisitorCard::where('visitor_card_no', $value->visitor_card_id)
                            ->where('residence_id', $residence_id)
                            ->first();

                        if (! isset($mmb2_visitor_card)) {
                            $visitor_card = VisitorCard::create([
                                'id' => $value->id,
                                'is_custom' => 1,
                                'residence_id' => $residence_id,
                                'visitor_card_no' => $value->visitor_card_no,
                                'created_at' => now(),
                            ]);
                        }
                    }
                });

            DB::commit();

            return true;
        } catch (Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }
}
