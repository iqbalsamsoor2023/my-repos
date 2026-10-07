<?php

namespace App\Services;

use Qcloud\Cos\Client;
use RuntimeException;

/**
 * Class CloudObjectStorageService.
 */
class CloudObjectStorageService
{
    public static function execute(): Client
    {
        $secretId = (string) config('filesystems.disks.cos.secret_id', '');
        $secretKey = (string) config('filesystems.disks.cos.secret_key', '');
        $region = (string) config('filesystems.disks.cos.region', '');

        if ($secretId === '' || $secretKey === '' || $region === '') {
            throw new RuntimeException('COS configuration is missing. Check filesystems.disks.cos in config/filesystems.php and the loaded environment.');
        }

        $cosClient = new Client(
            [
                'region' => $region,
                'schema' => 'https',
                'credentials' => [
                    'secretId' => $secretId,
                    'secretKey' => $secretKey,
                ],
                'timezone' => 'Asia/Bangkok',
            ]
        );

        return $cosClient;
    }
}
