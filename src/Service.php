<?php

namespace iMemento\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
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
     * ['token_time'] int The time in minutes the consumer token is stored
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
    protected $consumer_token;

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
     */
    protected function getPermissions()
    {
        $response = $this->permissions->authorize($this->user_token, $this->config['service_id']);
        $body = json_decode($response->getBody());

        //if the auth token is expired, refresh it and try perms again
        if ($response->getStatusCode() == 401 && $body->code == 1002) {
            $this->refreshUserToken();
            $this->getPermissions(); //tries getting permissions with the new auth token
        }

        //if the response is ok, update the perms token
        if($response->getStatusCode() == 200)
            $this->perms_token = $body;

        return $this->perms_token;
    }

    /**
     * Creates the consumer token
     *
     * @return mixed
     */
    public function createConsumerToken()
    {
        $payload = Payload::create([
            'iss' => $this->issuer->name,
            'perms' => $this->perms_token,
        ]);

        $this->consumer_token = JWT::encode($payload, $this->issuer->private_key);

        return $this->consumer_token;
    }

    /**
     * Returns the consumer token used in the request to the service
     *
     * @return string
     */
    protected function getConsumerToken()
    {
        //if token already loaded, return it
        if (!is_null($this->consumer_token))
            return $this->consumer_token;

        //try getting from cache
        $this->consumer_token = $this->retrieveConsumerToken();
        if ($this->consumer_token)
            return $this->consumer_token;

        //if no perms_token, get it
        if(!$this->perms_token)
            $this->getPermissions();

        //create a new token
        $this->createConsumerToken();


        //store the token
        $this->storeConsumerToken();

        return $this->consumer_token;
    }

    /**
     * @throws InvalidTokenException
     */
    public function refreshUserToken()
    {
        $this->user_token = $this->authentication->getToken();

        //if in the range of allowed attempts, retry - otherwise throw error
        if ($this->auth_attempts < 1) {
            $this->auth_attempts++;
        } else {
            throw new InvalidTokenException('The User token could not be refreshed.');
        }
    }

    /**
     * @throws InvalidTokenException
     */
    public function refreshPermsToken()
    {
        $this->perms_token = $this->permissions->authorize($this->user_token, $this->config['service_id']);

        //if in the range of allowed attempts, retry - otherwise throw error
        if ($this->perms_attempts < 1) {
            $this->perms_attempts++;
        } else {
            throw new InvalidTokenException('The Perms token could not be refreshed.');
        }
    }

    /**
     * @throws InvalidTokenException
     */
    public function refreshConsumerToken()
    {
        $this->consumer_token = $this->permissions->authorize($this->user_token, $this->config['service_id']);

        //if in the range of allowed attempts, retry - otherwise throw error
        if ($this->perms_attempts < 1) {
            $this->perms_attempts++;
        } else {
            throw new InvalidTokenException('The Consumer token could not be refreshed.');
        }

        $this->storeConsumerToken();
    }

    /**
     * @return mixed
     */
    public function getUserToken()
    {
        return $this->user_token;
    }

    /**
     * @return mixed
     */
    public function getPermsToken()
    {
        return $this->perms_token;
    }

    /**
     * Retrieves the consumer token from cache
     *
     * @return mixed
     */
    public function retrieveConsumerToken()
    {
        $key = $this->issuer->name .':'. $this->config['service_id'];

        if($this->user_token)
            $key .= ':'. md5($this->user_token);

        return $this->issuer->token_store->get($key);
    }

    /**
     * Stores the consumer token in cache
     *
     * @return mixed
     */
    protected function storeConsumerToken()
    {
        //if the token_store is null, return
        if(is_null($this->issuer->token_store))
            return;

        $minutes = !empty($this->config['token_time']) ? $this->config['token_time'] : 48 * 60 * 60;

        $key = $this->issuer->name .':'. $this->config['service_id'];

        if($this->user_token)
            $key .= ':'. md5($this->user_token);

        return $this->issuer->token_store->put($key, $this->consumer_token, $minutes);
    }

    /**
     * @param string $method
     * @param string $url
     * @param array  $data
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws InvalidTokenException
     */
    protected function call(string $method, string $url, array $data = null)
    {
        $url = $this->config['endpoint'] . $url;

        $this->getConsumerToken();

        //TODO: test the tokens refresh and what gets stored in cache!!!
        //TODO: also how the ClientException is handled in the other methods for guzzle

        //guzzle config
        $data['headers']['Authorization'] = 'Bearer ' . $this->consumer_token;
        $data['headers']['Host'] = $this->config['host'];

        try {

            $response = $this->caller->request($method, $url, $data);
            $body = json_decode($response->getBody(), true);
            return $body;

        } catch (ClientException $e) {

            //if the service returns 401, we handle the tokens refresh
            if ($e->getResponse()->getStatusCode() == 401) {
                $code = json_decode($e->getResponse()->getBody())->code;
                $this->handleTokensRefresh($code, $method, $url, $data);
            }
            return;
        }
    }

    /**
     * @param $code
     * @param $method
     * @param $url
     * @param $data
     */
    public function handleTokensRefresh($code, $method, $url, $data)
    {
        //if the auth token is expired, refresh it, get perms and make the call again
        if ($code == 1002) {
            $this->refreshUserToken();
            $this->getPermissions(); //tries getting permissions with the new auth token
            $this->refreshConsumerToken();

            //if the perms token is expired, refresh it and make the call again
        } elseif ($code == 1003) {
            $this->refreshPermsToken();
            $this->refreshConsumerToken();

            //if the consumer token is expired, refresh it and make the call again
        } elseif ($code == 1004) {
            $this->refreshConsumerToken();
        }

        $this->call($method, $url, $data);
    }

    /**
     * @param $url
     * @param $data
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    protected function _post(string $url, array $data = null)
    {
        return $this->call('POST', $url, $data);
    }

    /**
     * @param $url
     * @param $data
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    protected function _get(string $url, array $data = null)
    {
        return $this->call('GET', $url, $data);
    }

    /**
     * @param $url
     * @param $data
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    protected function _put(string $url, array $data = null)
    {
        return $this->call('PUT', $url, $data);
    }

    /**
     * @param $url
     * @param $data
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    protected function _delete(string $url, array $data = null)
    {
        return $this->call('DELETE', $url, $data);
    }

}