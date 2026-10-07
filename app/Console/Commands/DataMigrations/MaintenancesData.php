<?php

namespace App\Console\Commands\DataMigrations;

use App\Models\Amenity;
use App\Models\Maintenance;
use App\Models\MaintenanceProgression;
use App\Models\Unit;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class MaintenancesData
{
    public static function execute($residence_id)
    {
        try {
            DB::beginTransaction();

            $mmb1Data = DB::connection('mmb1')
                ->table('maintenances')
                ->where('residence_id', $residence_id)
                ->orderBy('id', 'asc')
                ->chunk(1000, function ($datas) use ($residence_id) {
                    $mmb2Data = [];

                    foreach ($datas as $key => $value) {
                        $mmb2_amenity = null;
                        $mmb1_facility = null;
                        $maintainable_id = 0;
                        $miscellaneous = null;

                        $miscellaneous = $value->remark ? explode(',', $value->remark)[0] : null;

                        if ($value->type_id == 1) { // public
                            $mmb1_facility = DB::connection('mmb1')
                                ->table('facilities')
                                ->where('id', $value->entity_id)
                                ->first();

                            if (isset($mmb1_facility)) {
                                $maintainable_id = DB::table('claimable_items')
                                    ->where('residence_id', $residence_id)
                                    ->where('item', $mmb1_facility->name)
                                    ->value('id');
                            } else {
                                $maintainable_id = DB::table('claimable_items')
                                    ->where('residence_id', $residence_id)
                                    ->where('item', 'Others')
                                    ->value('id');
                            }
                        } elseif ($value->type_id == 2) { // private
                            $mmb1_unit = DB::connection('mmb1')
                                ->table('residence_units')
                                ->where('id', $value->entity_id)
                                ->first();

                            $mmb2_unit = Unit::withTrashed()->where('home_id', $mmb1_unit->home_id)
                                ->where('unit_number', $mmb1_unit->unit)
                                ->first();
                            $maintainable_id = $mmb2_unit->id;

                            if (empty($value->private_amenities_id)) {
                                $mmb2_amenity = Amenity::withTrashed()->where('amenity_name', 'Others')->where('residence_id', $residence_id)->first();
                            } else {
                                if ($value->private_amenities_id) {
                                    $mmb1_amenity = DB::connection('mmb1')
                                        ->table('private_amenities')
                                        ->where('id', $value->private_amenities_id)
                                        ->first();
                                    if (isset($mmb1_amenity)) {
                                        $mmb2_amenity = Amenity::withTrashed()->where('amenity_name', $mmb1_amenity->amenity_name)
                                            ->where('residence_id', $mmb1_amenity->residence_id)
                                            ->where('created_at', $mmb1_amenity->created_at)
                                            ->first();
                                    } else {
                                        $mmb2_amenity = Amenity::withTrashed()->where('amenity_name', 'Others')->where('residence_id', $residence_id)->first();
                                    }
                                }
                            }
                        }

                        $mmb1_user = DB::connection('mmb1')
                            ->table('users')
                            ->where('id', $value->created_by)
                            ->first();

                        $mmb2_user = User::withTrashed()->where('email', $mmb1_user->email)->first();

                        if (! empty($mmb2_user)) {
                            $maintenance = Maintenance::withoutEvents(function () use ($value, $mmb2_user, $maintainable_id, $mmb2_amenity, $miscellaneous) {
                                return Maintenance::create([
                                    'maintainable_id' => $maintainable_id ?? 0,
                                    'maintainable_type' => $value->type_id == 1 ? 'App\Models\ClaimableItem' : 'App\Models\Unit',
                                    'amenity' => isset($mmb2_amenity) ? $mmb2_amenity : null, // {"id": 4, "remark": null, "supplier": "Plumber", "period_type": "year", "amenity_name": "Water Pipe", "is_out_warranty": 0, "warranty_period": 3}
                                    'issue_description' => $value->remark,
                                    'appointment_datetime' => $value->appointment_date_time == '0000-00-00 00:00:00' ? null : $value->appointment_date_time,
                                    'status' => $value->status,
                                    'is_verified' => $value->verification == 1 ? 1 : 0,
                                    'verification_description' => $value->verification_remark,
                                    'miscellaneous' => $miscellaneous,
                                    'reported_by' => $mmb2_user->id,
                                    'rating' => $value->rating,
                                    'completed_remark' => null,
                                    'created_at' => $value->created_at,
                                    'updated_at' => $value->updated_at,
                                ]);
                            });

                            $mmb1_maintenance_progresses = DB::connection('mmb1')
                                ->table('maintenance_progresses')
                                ->where('maintenance_id', $value->id)
                                ->get();

                            foreach ($mmb1_maintenance_progresses as $mmb1_maintenance_progress) {
                                $mmb1_user = DB::connection('mmb1')
                                    ->table('users')
                                    ->where('id', $mmb1_maintenance_progress->created_by)
                                    ->first();
                                MaintenanceProgression::create([
                                    'maintenance_id' => $maintenance->id,
                                    'progress_description' => $mmb1_maintenance_progress->remark,
                                    'created_by' => $mmb1_user->id,
                                    'created_at' => $mmb1_maintenance_progress->created_at,
                                    'updated_at' => $mmb1_maintenance_progress->updated_at,
                                ]);
                            }

                            $completed = null;
                            $completed = DB::connection('mmb1')
                                ->table('maintenance_progresses')
                                ->where('status', 2)
                                ->where('maintenance_id', $value->id)
                                ->first();

                            $maintenance->timestamps = false;
                            $maintenance->update([
                                'completed_remark' => $completed->remark ?? null,
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
