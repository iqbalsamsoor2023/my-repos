<?php

namespace App\Jobs;

use App\Enums\Visitor\ArrivalType;
use App\Enums\Visitor\EstampByType;
use App\Models\PreregisterVisitor;
use App\Models\Unit;
use App\Models\User;
use App\Models\VisitingArrangement;
use App\Models\Visitor;
use App\Models\VisitorCard;
use App\Models\VisitorLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MigratePrebookVisitorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;

    protected $residence_id;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data, $residence_id)
    {
        $this->onQueue('migrateVisitorDataQueue');
        $this->data = $data;
        $this->residence_id = $residence_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $value = $this->data;
        $residence_id = $this->residence_id;

        $data = [
            'name' => $value->name ?? '-',
            'contact_no' => $value->contact_no,
            'id_type' => $value->id_type ?? null,
            'id_number' => $value->id_no ?? null,
            'created_at' => $value->created_at,
            'updated_at' => $value->updated_at,
        ];
        $visitor = Visitor::updateOrCreate($data, $data);
        $mmb2_visitor_card = VisitorCard::where('visitor_card_no', $value->visitor_card_id)
            ->where('residence_id', $residence_id)
            ->first();

        $vehicle_info = null;
        if (isset($value->vehicle_province) && isset($value->vehicle_no) && isset($value->vehicle_brand)) {
            $vehicle_info = [
                'province' => $value->vehicle_province,
                'lp_number' => $value->vehicle_no,
                'vehicle_brand' => $value->vehicle_brand,
                'vehicle_color' => $value->vehicle_color,
                'vehicle_model' => $value->vehicle_model,
            ];
        }

        $is_allowed = null;
        if ($value->blacklist_type == 1 || (isset($value->remark) && $value->remark != 'No Entry!')) {
            $is_allowed = 1;
        } elseif ($value->blacklist_type == 2 || $value->remark == 'No Entry!') {
            $is_allowed = 0;
        }

        if ($value->type_id == 3 && ! empty($value->arrive_at)) { // for prebook visitor that already checked in

            $mmb2_visitor_log = VisitorLog::create([
                'visitor_card_id' => $mmb2_visitor_card->id ?? null,  // recheck if it store visitor card no or id
                'visitor_code' => $value->code,
                'visitor_purpose' => $value->purpose_of_visit,
                'visitor_generated_no' => $value->run_visitor_no,
                'company_name' => $value->company_name,
                'visitor_id' => $visitor->id,
                'arrival_type' => ! empty($value->vehicle_no) ? ArrivalType::DRIVE_IN->value : ArrivalType::WALK_IN->value,
                'vehicle_type' => $value->vehicle_type,
                'vehicle_plate_no' => $value->vehicle_no,
                'vehicle_info' => $vehicle_info ? json_encode($vehicle_info) : null,
                'arrival_time' => $value->arrive_at,
                'leave_time' => $value->depart_at,
                'temperature' => $value->temperature,
                'passenger_count' => $value->follower,
                'remark' => $value->visitor_remark,
                'blacklist_remark' => $value->blacklist_type == 2 ? 'No Entry' : $value->remark,
                'is_allowed' => $is_allowed,
                'is_pre_register' => 1,
                'created_at' => $value->created_at,
                'updated_at' => $value->updated_at,
            ]);
            $this->createVisitingArrangements($value, $mmb2_visitor_log, $visitor);
            $this->createPreregisterVisitor($value, $visitor);
        } elseif ($value->type_id == 3 && empty($value->arrive_at) && (! empty($value->start_date) || ! empty($value->visit_at))) { // prebook visitor that not yet checked in
            $this->createPreregisterVisitor($value, $visitor);
        } else {
            Log::info('visitors');
            Log::info($value->id);
        }
    }

    public function createPreregisterVisitor($value, $visitor)
    {
        $mmb2_user = null;
        if (isset($value->residence_user_id)) {
            $mmb1_residence_user = DB::connection('mmb1')
                ->table('residence_users')
                ->where('id', $value->residence_user_id)
                ->first();

            $mmb1_user = DB::connection('mmb1')
                ->table('users')
                ->where('id', $mmb1_residence_user->user_id ?? null)
                ->first();
            $mmb2_user = User::withTrashed()->where('email', $mmb1_user->email ?? null)->first();
        } else {
            $va = DB::connection('mmb1')
                ->table('visiting_arrangements')
                ->select('*', 'visiting_arrangements.id as id', 'visiting_arrangements.created_at as created_at', 'visiting_arrangements.updated_at as updated_at')
                ->leftJoin('residence_units', 'residence_units.id', 'visiting_arrangements.residence_unit_id')
                ->where('visitor_id', $value->id)->first();

            if (isset($va)) {
                $mmb1_residence_user = DB::connection('mmb1')
                    ->table('residence_users')
                    ->where('id', $va->residence_user_id)
                    ->first();

                $mmb1_user = DB::connection('mmb1')
                    ->table('users')
                    ->where('id', $mmb1_residence_user->user_id ?? null)
                    ->first();
                $mmb2_user = User::withTrashed()->where('email', $mmb1_user->email ?? null)->first();
            }
        }

        if (isset($value->residence_unit_id)) {
            $mmb1_residence_unit = DB::connection('mmb1')
                ->table('residence_units')
                ->where('id', $value->residence_unit_id)
                ->first();

            $mmb2_unit = Unit::withTrashed()->where('home_id', $mmb1_residence_unit->home_id ?? null)->where('unit_number', $mmb1_residence_unit->unit ?? null)->first();
        } else {
            $va = DB::connection('mmb1')
                ->table('visiting_arrangements')
                ->select('*', 'visiting_arrangements.id as id', 'visiting_arrangements.created_at as created_at', 'visiting_arrangements.updated_at as updated_at')
                ->leftJoin('residence_units', 'residence_units.id', 'visiting_arrangements.residence_unit_id')
                ->where('visitor_id', $value->id)->first();

            if (isset($va)) {
                $mmb1_residence_unit = DB::connection('mmb1')
                    ->table('residence_units')
                    ->where('id', $va->residence_unit_id)
                    ->first();

                $mmb2_unit = Unit::withTrashed()->where('home_id', $mmb1_residence_unit->home_id ?? null)->where('unit_number', $mmb1_residence_unit->unit ?? null)->first();
            }
        }

        $pre_register_visitor_data = [
            'visitor_id' => $visitor->id,
            'visitor_code' => $value->code,
            'arrival_type' => ! empty($value->vehicle_no) ? ArrivalType::DRIVE_IN->value : ArrivalType::WALK_IN->value,
            'vehicle_type' => $value->vehicle_type,
            'vehicle_plate_no' => $value->vehicle_no,
            'is_multiple_entry' => isset($value->start_date) ? 1 : 0,
            'validity_start_date' => $value->start_date ?? $value->visit_at,
            'validity_end_date' => $value->end_date,
            'unit_id' => $mmb2_unit->id ?? null,
            'user_id' => $mmb2_user->id ?? null,
            'is_qr_code_expired' => $value->status == 4 ? 1 : 0,
            'visitor_purpose' => $value->purpose_of_visit,
            'created_at' => $value->created_at,
            'updated_at' => $value->updated_at,
        ];

        PreregisterVisitor::create($pre_register_visitor_data);
    }

    public function createVisitingArrangements($value, $mmb2_visitor_log, $visitor)
    {
        $mmb1_visiting_arrangements = DB::connection('mmb1')
            ->table('visiting_arrangements')
            ->select('*', 'visiting_arrangements.id as id', 'visiting_arrangements.created_at as created_at', 'visiting_arrangements.updated_at as updated_at')
            ->leftJoin('residence_units', 'residence_units.id', 'visiting_arrangements.residence_unit_id')
            ->where('visitor_id', $value->id)
            ->orderBy('visiting_arrangements.id')
            ->chunk(100, function ($datas, $unit) use ($value, $mmb2_visitor_log) {
                foreach ($datas as $mmb1_visiting_arrangement) {
                    $mmb2_unit = Unit::withTrashed()->where('home_id', $mmb1_visiting_arrangement->home_id)->where('unit_number', $mmb1_visiting_arrangement->unit)->first();

                    $residence_user = DB::connection('mmb1')
                        ->table('residence_users')
                        ->where('id', $mmb1_visiting_arrangement->residence_user_id)
                        ->first();

                    if (isset($residence_user)) {
                        $mmb1_user = DB::connection('mmb1')
                            ->table('users')
                            ->where('id', $residence_user->user_id)
                            ->first();
                        if (isset($mmb1_user)) {
                            $user = User::withTrashed()->where('email', $mmb1_user->email)->first();
                        }
                    }
                    if (in_array($mmb1_visiting_arrangement->estamp_by_type, [EstampByType::RESIDENT->value, EstampByType::PM->value])) {
                        $mmb1_user = DB::connection('mmb1')
                            ->table('users')
                            ->where('id', $mmb1_visiting_arrangement->estamp_by)
                            ->first();
                        $estamp_by = User::withTrashed()->where('email', $mmb1_user->email)->first();
                    } elseif (in_array($mmb1_visiting_arrangement->estamp_by_type, [EstampByType::SG->value, EstampByType::SGOC->value])) {
                        $mysgoc1_user = DB::connection('sgoc1')
                            ->table('users')
                            ->where('id', $mmb1_visiting_arrangement->estamp_by)
                            ->first();

                        if (isset($mmb1_user)) {
                            $estamp_by = DB::connection('sgoc')
                                ->table('users')
                                ->where('email', $mmb1_user->email)
                                ->first();
                        }
                    }

                    $visiting_arrangement = VisitingArrangement::create([
                        'visitor_log_id' => $mmb2_visitor_log->id,
                        'residence_id' => $value->residence_id,
                        'unit_id' => $mmb2_unit->id,
                        'user_id' => isset($user) ? $user->id : null,
                        'estamp_by' => $estamp_by->id ?? null,
                        'estamp_by_type' => $mmb1_visiting_arrangement->estamp_by_type,
                        'status' => $mmb1_visiting_arrangement->status,
                        'feedback_remark' => $mmb1_visiting_arrangement->feedback_remark,
                        'created_at' => $mmb1_visiting_arrangement->created_at,
                        'updated_at' => $mmb1_visiting_arrangement->updated_at,
                    ]);
                }
            });
    }
}
