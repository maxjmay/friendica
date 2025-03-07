<?php

namespace Friendica\Module\Security;

use Friendica\Core\Worker;
use Friendica\DI;
use Friendica\Security\OAuth;
use Friendica\Module\UserDeviceManager;
use Friendica\Model\User;
use Friendica\Util\DateTimeFormat;

class LoginWithMobileApp extends BaseApi
{
    public function post()
    {
        $username = $this->getInput('username');
        $password = $this->getInput('password');
        $client_id = $this->getInput('client_id');
        $device_push_token = $this->getInput('device_push_token');
        
        // Ensure the inputs are provided
        if (empty($username) || empty($password) || empty($client_id) || empty($device_push_token)) {
            return $this->error('Missing required parameters.');
        }
        
        // Check if the application is valid
        $application = OAuth::getApplicationForMobileAppLogin($client_id, $this->getInput('redirect_uri'));
        if (empty($application)) {
            return $this->error('Invalid client credentials.');
        }

        // Authenticate the user with username and password
        try {
            $user_id = User::getIdFromPasswordAuthentication($username, $password);
            if (!$user_id) {
                return $this->error('Invalid credentials.');
            }

            // Create an access token for the user and the application
            $scope = 'read write follow push'; // Define the scope, adjust as needed
            $token = OAuth::createTokenForUser($application, $user_id, $scope);

            UserDeviceManager::addDevicePushToken($user_id, $device_push_token);
            
            if (empty($token)) {
                return $this->error('Failed to generate token.');
            }

            // Return the token to the mobile app
            return $this->response([
                'access_token' => $token['access_token'],
                'expires_in' => 3600,  // Example expiration time (1 hour)
                'token_type' => 'Bearer',
            ]);

        } catch (Exception $e) {
            DI::logger()->error('Login failed: ' . $e->getMessage());
            return $this->error('Login failed.');
        }
    }
}