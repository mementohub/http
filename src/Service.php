<?php

namespace iMemento\Http;

use GuzzleHttp\Client;
use iMemento\Exceptions\InvalidTokenException;
use iMemento\JWT\Issuer;
use iMemento\JWT\JWT;
use iMemento\JWT\Payload;
use iMemento\SDK\Authentication\Client as AuthClient;
use iMemento\SDK\Permissions\Client as PermsClient;

/**
 * Class Service
 *
 * @package iMemento\Http
 */
abstract class Service
{

    /**
     * @var array
     */
    protected $config;

    /**
     * @var
     */
    protected $issuer;

    /**
     * @var
     */
    protected $caller;

    /**
     * @var
     */
    protected $authentication;

    /**
     * @var
     */
    protected $permissions;

    /**
     * @var
     */
    protected $user_token;

    /**
     * @var
     */
    protected $perms_token;

    /**
     * @var
     */
    protected $token;

    /**
     * @var
     */
    protected $auth_attempts = 0;

    /**
     * @var
     */
    protected $perms_attempts = 0;


    /**
     * Service constructor.
     *
     * @param Issuer $issuer
     * @param $config
     */
    public function __construct(Issuer $issuer = null, array $config = [])
    {
        $this->config = array_merge($this->config, $config);

        $this->issuer = $issuer;

        $this->authentication = new AuthClient($issuer);

        $this->permissions = new PermsClient($issuer);

        $this->caller = new Client([
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);
    }

    /**
     * @param $token
     * @return $this
     */
    public function setUserToken($token)
    {
        $this->user_token = $token;

        return $this;
    }

    /**
     * Returns the permissions token
     *
     * @return mixed
     * @throws InvalidTokenException
     */
    protected function getPermissions()
    {
        $response = $this->permissions->authorize($this->user_token, $this->issuer->name);
        $body = json_decode($response->getBody());

        //if the auth token is expired, refresh it and try perms again
        if ($response->getStatusCode() == 401 && $body->code == 1002) {
            $this->refreshAuthToken();
        }

        $this->perms_token = $body;

        return $this->perms_token;
    }

    /**
     * Returns the consumer token used in the request to the service
     *
     * @return string
     */
    protected function getToken()
    {
        if(!is_null($this->token)) {
            return $this->token;
        }

        $permissions = $this->getPermissions();

        $payload = Payload::create([
            'iss' => $this->issuer->name,
            'perms' => $permissions,
        ]);

        $this->token = JWT::encode($payload, $this->issuer->private_key);

        return $this->token;
    }

    /**
     * @throws InvalidTokenException
     */
    protected function refreshAuthToken()
    {
        $this->user_token = $this->authentication->refreshToken($this->user_token);

        //if in the range of allowed attempts, retry - otherwise throw error
        if($this->auth_attempts < 1) {
            $this->auth_attempts++;
        } else {
            throw new InvalidTokenException('The Auth token could not be refreshed.');
        }
    }

    /**
     * @throws InvalidTokenException
     */
    protected function refreshPermsToken()
    {
        $this->perms_token = $this->permissions->refreshToken($this->user_token);

        //if in the range of allowed attempts, retry - otherwise throw error
        if($this->perms_attempts < 1) {
            $this->perms_attempts++;
        } else {
            throw new InvalidTokenException('The Perms token could not be refreshed.');
        }
    }


    //TODO: make sure permissions call is not made unless necessary
    //getpermissions shouldn't be called on refresh i think


    /**
     * @param string $method
     * @param string $url
     * @param        $data
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws InvalidTokenException
     */
    private function call(string $method, string $url, $data = null)
    {
        $url = $this->config['endpoint'] . $url;

        $data['headers']['Authorization'] = 'Bearer ' . $this->getToken();
        $data['headers']['Host'] = $this->config['host'];

        $response = $this->caller->request($method, $url, $data);
        $body = json_decode($response->getBody());

        //if the auth token is expired, refresh it, get perms and make the call again
        if ($response->getStatusCode() == 401 && $body->code == 1002) {
            $this->refreshAuthToken();
            $this->getPermissions();
            $this->call($method, $url, $data);

        //if the perms token is expired, refresh it and make the call again
        } elseif ($response->getStatusCode() == 401 && $body->code == 1003) {
            $this->refreshPermsToken();
            $this->call($method, $url, $data);
        }

        return json_decode($response->getBody(), true);
    }

    /**
     * @param $url
     * @param $data
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    protected function _post($url, $data = null)
    {
        return $this->call('POST', $url, $data);
    }

    /**
     * @param $url
     * @param $data
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    protected function _get($url, $data = null)
    {
        return $this->call('GET', $url, $data);
    }

    /**
     * @param $url
     * @param $data
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    protected function _put($url, $data = null)
    {
        return $this->call('PUT', $url, $data);
    }

    /**
     * @param $url
     * @param $data
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    protected function _delete($url, $data = null)
    {
        return $this->call('DELETE', $url, $data);
    }

}