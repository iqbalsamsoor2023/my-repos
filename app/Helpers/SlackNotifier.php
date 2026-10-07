<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;

class SlackNotifier
{
    public static function send(string $message): void
    {
        $url = config('services.partition_slack.webhook_url');

        if (!$url) {
            return;
        }

        Http::post($url, [
            'text' => $message,
        ]);
    }
}
