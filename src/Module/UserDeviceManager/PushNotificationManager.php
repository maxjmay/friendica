<?php

namespace Friendica\Module\UserDeviceManager;

use GuzzleHttp\Client;

class PushNotificationManager
{
    /**
     * Send a push notification to a user using Expo.
     * 
     * @param int $user_id
     * @param string $title
     * @param string $message
     * @return bool
     */
    public static function sendPushNotification(int $user_id, string $title, string $message): bool
    {
        $tokens = UserDeviceManager::getUserDeviceTokens($user_id);

        if (empty($tokens)) {
            return false; // No devices found for the user
        }

        $client = new Client();
        $endpoint = "https://exp.host/--/api/v2/push/send";

        $notifications = [];
        foreach ($tokens as $token) {
            $notifications[] = [
                "to" => $token,
                "title" => $title,
                "body" => $message,
                "sound" => "default",
                "data" => ["extra_data" => "some_value"]
            ];
        }

        try {
            $response = $client->post($endpoint, [
                'json' => $notifications,
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json'
                ]
            ]);

            $responseData = json_decode($response->getBody(), true);
            return isset($responseData['data']);
        } catch (\Exception $e) {
            DI::logger()->error('Failed to send push notification', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
