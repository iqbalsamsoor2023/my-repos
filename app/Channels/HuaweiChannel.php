<?php

namespace App\Channels;

use App\Models\HuaweiAccessToken;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Notifications\Notification;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class HuaweiChannel
{
    protected $logger;

    public function __construct()
    {
        // Create a new logger instance
        $this->logger = new Logger('my-logger');

        // Add a handler to write log messages to a file
        $this->logger->pushHandler(new StreamHandler('storage/logs/huawei-notification.log', Logger::DEBUG));
    }

    /**
     * Send the given notification.
     *
     * @param  mixed  $notifiable
     * @param Notification $notification
     * @return void
     */
    public function send($notifiable, Notification $notification)
    {
        // Try to get an access token
        $accessToken = $this->getToken($notification);
        // Try to send the notification using the access token
        $response = $this->sendNotification($notifiable, $notification, $accessToken);

        // If the API request fails, retry up to 3 times
        $retryCount = 0;
        while ($response['code'] !== '80000000' && $retryCount < 3) {
            $retryCount++;

            // Log the error and sleep for a short period before retrying
            $errorMessage = $response['msg'];
            $this->logger->error("Failed to send notification: $errorMessage. Retrying in 5 seconds...");
            sleep(5);

            // Try to get a new access token
            $accessToken = $this->getToken($notification);

            // Try to send the notification using the new access token
            $response = $this->sendNotification($notifiable, $notification, $accessToken);
        }

        // If the API request still fails after retrying, throw an exception
        if ($response['code'] !== '80000000') {
            throw new Exception($response['msg']);
        }
    }

    private function sendNotification($notifiable, Notification $notification, string $accessToken)
    {
        // Get the device tokens
        $huaweiDevices = $notifiable->devices()
            ->whereNotNull('huawei_token')
            ->pluck('huawei_token')
            ->toArray();

        $deviceToken = $huaweiDevices;

        // Define the recipient information
        if (is_string($deviceToken)) {
            $recipient = [
                'token' => [$deviceToken],
            ];
        } else {
            $recipient = [
                'token' => $deviceToken,
            ];
        }

        // Define the message data
        $messageData = [
            'android' => [
                'collapse_key' => -1,
                'ttl' => '1448s',
                'fast_app_target' => 2,
                'notification' => [
                    'title' => $notification->title,
                    'body' => $notification->body,
                    'default_sound' => true,
                    'importance' => 'NORMAL',
                    'click_action' => [
                        'type' => 3,
                    ],
                ],
                'notify_id' => $notification->id,
            ],
            'token' => $recipient['token'],
        ];

        // Define the request payload
        $requestData = [
            'validate_only' => false,
            'message' => $messageData,
        ];

        // Define the request headers
        $requestHeaders = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer '.$accessToken,
        ];

        // Make the API request using the Guzzle HTTP client
        $client = new Client;
        $response = $client->post(
            'https://push-api.cloud.huawei.com/v1/'.$notification->huaweiAppId.'/messages:send',
            [
                'headers' => $requestHeaders,
                'json' => $requestData,
            ]
        );

        $responseBody = json_decode($response->getBody(), true);

        return $responseBody;
    }

    private function getToken(Notification $notification)
    {
        // Use the accessor to get the age of the access token
        $tokenChecker = HuaweiAccessToken::where('token_type', $notification->huaweiTokenType)->first();
        if (is_null($tokenChecker)) {
            $token = $this->obtainedAccessToken($notification);

            return $token;
        } else {
            $expiresIn = $tokenChecker->expires_in;
            // If the current time greater than access token created_at, get a new one
            if (Carbon::now() >= $expiresIn) {
                $token = $this->refreshAccessToken($notification);

                return $token;
            } else {
                $token = $tokenChecker->access_token;

                return $token;
            }
        }
    }

    private function refreshAccessToken(Notification $notification)
    {
        $client = new Client;
        $response = $client->post('https://oauth-login.cloud.huawei.com/oauth2/v3/token', [
            'form_params' => [
                'grant_type' => 'client_credentials',
                'client_id' => $notification->huaweiAppId,
                'client_secret' => $notification->huaweiClientSecret,
            ],
        ]);

        $data = json_decode((string) $response->getBody(), true);
        $tokenData = [
            'access_token' => $data['access_token'],
            'expires_in' => $data['expires_in'],
        ];

        $huaweiAccessToken = HuaweiAccessToken::where('token_type', $notification->huaweiTokenType)->first();
        $huaweiAccessToken->token_type = $notification->huaweiTokenType;
        $huaweiAccessToken->access_token = $tokenData['access_token'];
        $huaweiAccessToken->expires_in = $tokenData['expires_in'];
        $huaweiAccessToken->touch();

        return $huaweiAccessToken->access_token;
    }

    private function obtainedAccessToken(Notification $notification)
    {
        $client = new Client;
        $response = $client->post('https://oauth-login.cloud.huawei.com/oauth2/v3/token', [
            'form_params' => [
                'grant_type' => 'client_credentials',
                'client_id' => $notification->huaweiAppId,
                'client_secret' => $notification->huaweiClientSecret,
            ],
        ]);

        $data = json_decode((string) $response->getBody(), true);
        $tokenData = [
            'access_token' => $data['access_token'],
            'expires_in' => $data['expires_in'],

        ];

        $huaweiAccessToken = new HuaweiAccessToken;
        $huaweiAccessToken->token_type = $notification->huaweiTokenType;
        $huaweiAccessToken->access_token = $tokenData['access_token'];
        $huaweiAccessToken->expires_in = $tokenData['expires_in'];
        $huaweiAccessToken->touch();

        return $huaweiAccessToken->access_token;
    }
}
