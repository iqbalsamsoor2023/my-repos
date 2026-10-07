<?php

namespace App\Console\Commands\DataMigrations;

use App\Jobs\MigrateFilesJobs;
use App\Models\Comment;
use App\Models\Maintenance;
use App\Models\User;
use App\Services\CloudObjectStorageService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CommentsData
{
    public static function execute($residence_id)
    {
        try {
            DB::beginTransaction();
            $mmb1Data = DB::connection('mmb1')
                ->table('private_claim_comments')
                ->select('*', 'private_claim_comments.id as id', 'private_claim_comments.created_at as created_at', 'private_claim_comments.updated_at as updated_at', 'maintenances.created_at as maintenance_created_at', 'maintenances.updated_at as maintenance_updated_at')
                ->leftJoin('maintenances', 'maintenances.id', 'private_claim_comments.maintenance_id')
                ->where('maintenances.residence_id', $residence_id)
                ->orderBy('private_claim_comments.id')
                ->chunk(1000, function ($datas) {
                    foreach ($datas as $key => $value) {
                        $cosClient = CloudObjectStorageService::execute();

                        // user_id
                        $mmb1_user = DB::connection('mmb1')
                            ->table('users')
                            ->where('id', $value->sender_user_id)
                            ->first();
                        $mmb2_user = User::withTrashed()->where('email', $mmb1_user->email)->first();

                        $mmb2_maintenance = Maintenance::where('updated_at', $value->maintenance_updated_at)->where('issue_description', $value->remark)->where('created_at', $value->maintenance_created_at)->first();

                        $comment = Comment::create([
                            'commentable_id' => $mmb2_maintenance->id,
                            'commentable_type' => 'App\Models\Maintenance',
                            'user_id' => $mmb2_user->id,
                            'content' => $value->text_comment,
                            'created_at' => $value->created_at,
                            'updated_at' => $value->updated_at,
                        ]);

                        $disk = Storage::disk('cos2');

                        $bucket = 'mooban-1258956757'; // Bucket name in the format of BucketName-APPID
                        $result = $cosClient->listObjects([
                            'Bucket' => $bucket,
                            'Prefix' => config('app.path.cos')."/private-claim-comment/$value->id/",
                        ]);

                        $images = [];
                        if (isset($result['Contents'])) {
                            foreach ($result['Contents'] as $rt) {
                                $images[] = $rt['Key'];
                            }
                        }

                        foreach ($images as $image) {
                            if ($disk->has($image)) {
                                $url = $disk->url($image);
                                MigrateFilesJobs::dispatch($url, $comment, ['type' => 'maintenance_comment'], 'comment_image');
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
