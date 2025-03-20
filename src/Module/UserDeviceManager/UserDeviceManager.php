<?php

namespace Friendica\Module\UserDeviceManager;

use Friendica\Database\DBA;

class UserDeviceManager
{
    /**
     * Add or update the device push notification token for the given user.
     *
     * @param int $user_id The user ID.
     * @param string $device_push_token The device push notification token.
     * @return bool Returns true on success, false on failure.
     */
    public static function addDevicePushToken(int $user_id, string $device_push_token): bool
    {
        // Check if the device token already exists
        $existingRecord = DBA::selectFirst('user-device', [], [
            'device-push-token' => $device_push_token
        ]);

        // If the device token exists with the same user_id, skip the insertion
        if ($existingRecord && $existingRecord['user_id'] === $user_id) {
            return false; // No need to insert, as it's the same user and token
        }

        // If the device token exists with a different user_id, delete the existing record
        if ($existingRecord && $existingRecord['user_id'] !== $user_id) {
            DBA::delete('user-device', [
                'device-push-token' => $device_push_token
            ]);
        }

        // Insert the new record
        $data = [
            'user_id' => $user_id,
            'device-push-token' => $device_push_token
        ];

        return DBA::insert('user-device', $data);
    }

    /**
     * Delete the device push notification token for the given user.
     *
     * @param int $user_id The user ID.
     * @return bool Returns true on success, false on failure.
     */
    public static function deleteDevicePushToken(int $user_id): bool
    {
        return DBA::delete('user-device', ['user_id' => $user_id]);
    }
    
    /**
     * Get all push notification tokens for a user.
     * 
     * @param int $user_id
     * @return array
     */
    public static function getUserDeviceTokens(int $user_id): array
    {
        $tokens = DBA::toArray(DBA::select('user_device', ['device_push_token'], ['user_id' => $user_id]));

        return array_column($tokens, 'device_push_token');
    }
}
?>