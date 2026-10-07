<?php

namespace App\Console\Commands\DataMigrations;

use App\Models\Announcement;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class AnnouncementsData
{
    public static function execute($residence_id)
    {
        try {
            DB::beginTransaction();
            $mmb1Data = DB::connection('mmb1')
                ->table('announcements')
                ->orderBy('announcements.id')
                ->whereJsonContains('residence_id', (int) $residence_id)
                ->chunk(1000, function ($datas) use ($residence_id) {
                    foreach ($datas as $key => $value) {
                        $announcementResidences = json_decode($value->residence_id);

                        if (empty($announcementResidences) == false) {
                            foreach ($announcementResidences as $key => $announcementResidence) {

                                // created by
                                $mmb1_created_by = DB::connection('mmb1')->table('users')->where('id', $value->created_by)->first();
                                $mmb2_created_by = User::withTrashed()->where('email', $mmb1_created_by->email)->first();

                                // updated by
                                if (isset($value->updated_by)) {
                                    $mmb1_updated_by = DB::connection('mmb1')->table('users')->where('id', $value->updated_by)->first();
                                    $mmb2_updated_by = User::withTrashed()->where('email', $mmb1_updated_by->email)->first();
                                }

                                if ($announcementResidence == $residence_id) {
                                    $announcement = Announcement::create([
                                        'residence_id' => $announcementResidence,
                                        'title' => $value->title,
                                        'description' => $value->description,
                                        'is_active' => $value->status,
                                        'created_by' => $mmb2_created_by->id,
                                        'updated_by' => $mmb2_updated_by->id,
                                        'created_at' => $value->created_at,
                                        'updated_at' => $value->updated_at,
                                    ]);
                                }
                            }
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
