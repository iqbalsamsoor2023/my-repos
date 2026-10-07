<?php

namespace App\Console\Commands\DataMigrations;

use App\Models\Event;
use App\Models\EventRsvp;
use App\Models\Residence;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class EventsData
{
    public static function execute($residence_id)
    {
        try {
            DB::beginTransaction();

            $mmb1Data = DB::connection('mmb1')
                ->table('events')
                ->select('*', 'events.id as id', 'events.created_at as created_at', 'events.updated_at as updated_at', 'residences.name as residence_name')
                ->join('residences', 'residences.id', 'events.residence_id')
                ->where('residences.id', $residence_id)
                ->orderBy('events.id')
                ->chunk(1000, function ($datas) {
                    foreach ($datas as $key => $value) {
                        $residence = Residence::withTrashed()->where('name', $value->residence_name)->latest()->first();

                        // created by
                        $mmb1_created_by = DB::connection('mmb1')->table('users')->where('id', $value->created_by)->first();
                        $mmb2_created_by = User::withTrashed()->where('email', $mmb1_created_by->email)->first();

                        // created by
                        $mmb1_updated_by = DB::connection('mmb1')->table('users')->where('id', $value->updated_by)->first();
                        $mmb2_updated_by = User::withTrashed()->where('email', $mmb1_created_by->email)->first();

                        $mmb2_event = Event::create([
                            'residence_id' => $residence->id,
                            'title' => $value->title,
                            'description' => $value->description,
                            'start_at' => $value->start_at,
                            'end_at' => $value->end_at,
                            'is_active' => $value->status,
                            'created_by' => $mmb2_created_by->id,
                            'updated_by' => $mmb2_updated_by->id,
                            'created_at' => $value->created_at,
                            'updated_at' => $value->updated_at,
                        ]);

                        $mmb1_event_rsvps = DB::connection('mmb1')
                            ->table('event_rsvps')
                            ->where('event_id', $value->id)
                            ->get();

                        foreach ($mmb1_event_rsvps as $mmb1_event_rsvp) {
                            $mmb1_user = DB::connection('mmb1')
                                ->table('users')
                                ->where('id', $mmb1_event_rsvp->user_id)
                                ->first();

                            $mmb2_user = User::withTrashed()->where('email', $mmb1_user->email)->first();

                            $mmb2_event_rsvp = EventRsvp::create([
                                'event_id' => $mmb2_event->id,
                                'user_id' => $mmb2_user->id,
                                'is_going' => $value->status,
                                'created_at' => $value->created_at,
                                'updated_at' => $value->updated_at,
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
