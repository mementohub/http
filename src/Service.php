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
     * ['service_id'] string Id of the consumed service
     * ['endpoint']   string The consumed service's url (kong)
     * ['host']       string The consumed service's host
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
     * @param bool $existing If true, returns the existing perms_token, otherwise tries to get a new one
     * @return mixed
     */
    protected function getPermissions($existing = true)
    {
        if (!is_null($this->perms_token) && $existing == true) {
            return $this->perms_token;
        }

        $response = $this->permissions->authorize($this->user_token, $this->config['service_id']);
        $body = json_decode($response->getBody());

        //if the auth token is expired, refresh it and try perms again
        if ($response->getStatusCode() == 401 && $body->code == 1002) {
            $this->refreshAuthToken();
            $this->getPermissions(false); //tries getting permissions with the new auth token
        }

        //if the response is ok, update the perms token
        if($response->getStatusCode() == 200)
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
        if (!is_null($this->token)) {
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

    //TODO: figure ou how to save the tokens in session

    /**
     * @throws InvalidTokenException
     */
    protected function refreshAuthToken()
    {
        $this->user_token = $this->authentication->getToken();

        //if in the range of allowed attempts, retry - otherwise throw error
        if ($this->auth_attempts < 1) {
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
        if ($this->perms_attempts < 1) {
            $this->perms_attempts++;
        } else {
            throw new InvalidTokenException('The Perms token could not be refreshed.');
        }
    }


    /**
     * @param string $method
     * @param string $url
     * @param        $data
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws InvalidTokenException
     */
    protected function call(string $method, string $url, $data = null)
    {
        $url = $this->config['endpoint'] . $url;

        //guzzle config
        $data['headers']['Authorization'] = 'Bearer ' . $this->getToken();
        $data['headers']['Host'] = $this->config['host'];

        $response = $this->caller->request($method, $url, $data);
        $body = json_decode($response->getBody(), true);

        //if the auth token is expired, refresh it, get perms and make the call again
        if ($response->getStatusCode() == 401 && $body['code'] == 1002) {
            $this->refreshAuthToken();
            $this->getPermissions(false); //tries getting permissions with the new auth token
            $this->call($method, $url, $data);

        //if the perms token is expired, refresh it and make the call again
        } elseif ($response->getStatusCode() == 401 && $body['code'] == 1003) {
            $this->refreshPermsToken();
            $this->call($method, $url, $data);
        }

        return $body; //TODO: should we return the whole response?
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