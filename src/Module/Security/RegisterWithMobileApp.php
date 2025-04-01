<?php

namespace Friendica\Module\Security;

use Friendica\App;
use Friendica\Core\Logger;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Security\OAuth;
use Friendica\Module\BaseApi;
use Friendica\Module\Special\HTTPException;
use Friendica\Module\UserDeviceManager\UserDeviceManager;
use Friendica\Model;
use Friendica\Model\User;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Profiler;
use Friendica\Util\Proxy;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ResponseInterface;

class RegisterWithMobileApp extends BaseApi
{

	public function run(HTTPException $httpException, array $request = [], bool $scopecheck = true): ResponseInterface
	{
		return parent::run($httpException, $request, false);
	}

    protected function post(array $request = [])
    {
		$arr = ['post' => $_POST];
        $username = $arr['post']['username'];
        $password = $arr['post']['password'];
        $client_id = $arr['post']['client_id'];
        $device_push_token = $arr['post']['device_push_token'];
        $redirect_uri = $arr['post']['redirect_uri'];

		$arr = $_POST;

        if (empty($username) || empty($password) || empty($client_id) || empty($device_push_token)) {
            return $this->error('Missing required parameters.');
        }
		
        try {
            //Check if nickname contains only US-ASCII and do not start with a digit
            if (!preg_match('/^[a-zA-Z][a-zA-Z0-9]*$/', $arr['nickname'])) {
                if (is_numeric(substr($arr['nickname'], 0, 1))) {
                    return $this->error('Nickname cannot start with a digit.');
                } else {
                    return $this->error('Nickname can only contain US-ASCII characters.');
                }
            }
            
            $arr['blocked'] = 0;
            $arr['verified'] = 1;

            try {
                $result = Model\User::create($arr);
            } catch (\Exception $e) {
                return $this->error('An error occurred.');
            }

            $user = $result['user'];

            $application = OAuth::getApplicationForMobileAppLogin($client_id, $redirect_uri);
            if (empty($application)) {
                return $this->error('Invalid client credentials.');
            }

            // Create an access token for the user and the application
            $scope = 'read write follow push'; // Define the scope, adjust as needed
            $token = OAuth::createTokenForUser($application, $user['id'], $scope);

            UserDeviceManager::addDevicePushToken($user['id'], $device_push_token);
            
            if (empty($token)) {
                return $this->error('Failed to generate token.');
            }

            // Return the token to the mobile app
            return $this->jsonExit([
                'access_token' => $token['access_token'],
                'refresh_token' => $token['refresh_token'],
                'expires_in' => 3600,
                'token_type' => 'Bearer',
            ]);
        } catch (Exception $e) {
            DI::logger()->error('Login failed: ' . $e->getMessage());
            return $this->error('Login failed.');
        }
	}
}
