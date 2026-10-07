<?php

namespace App\Console\Commands\DataMigrations;

use App\Models\VisitorCard;
use Exception;
use Illuminate\Support\Facades\DB;

class VisitorCardsData
{
    public static function execute($residence_id)
    {
        try {
            DB::beginTransaction();
            $mmb1Data = DB::connection('mmb1')
                ->table('visitor_cards')
                ->where('residence_id', $residence_id)
                ->orderBy('id', 'asc')
                ->chunk(1000, function ($datas) {
                    foreach ($datas as $key => $value) {
                        $visitor_card = VisitorCard::create([
                            'id' => $value->id,
                            'residence_id' => $value->residence_id,
                            'visitor_card_no' => $value->visitor_card_no,
                            'created_at' => $value->created_at,
                            'updated_at' => $value->updated_at,
                            'deleted_at' => $value->deleted_at,
                        ]);
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
