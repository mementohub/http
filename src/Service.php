<?php

namespace iMemento\Http;

use GuzzleHttp\Client;
use iMemento\JWT\Issuer;
use iMemento\JWT\JWT;
use iMemento\JWT\Payload;

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
    protected $permissions;

    /**
     * @var
     */
    protected $user_token;


    /**
     * @var
     */
    protected $token;


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

        $this->permissions = new Permissions;

        $this->caller = new Client([
            'headers' => [
                'Accept' 	=> 'application/json',
                'Host'     	=> $this->config['host'],
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
     * @return mixed
     */
    protected function getPermissions()
    {
        return $this->permissions->authorize($this->issuer, $this->user_token);
    }

    /**
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
     * @param string $method
     * @param string $url
     * @param        $data
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    private function call(string $method, string $url, $data = null)
    {
        $url = $this->config['endpoint'] . $url;

        $data['headers']['Authorization'] = 'Bearer ' . $this->getToken();

        $response = $this->caller->request($method, $url, $data);

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