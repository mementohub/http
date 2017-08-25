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
class Service
{

    /**
     * @var array
     */
    protected $config = [];

    /**
     * @var
     */
    protected $issuer;

    /**
     * @var
     */
    protected $permissions;

    /**
     * @var
     */
    protected $auth_token;


    /**
     * Service constructor.
     *
     * @param $config
     */
    public function __construct(array $config = [])
    {
        //TODO: guzzle has base_uri

        $this->config = array_merge($this->config, $config);

        $this->issuer = new Issuer($this->config['issuer'], $this->config['private_key']);

        $this->permissions = new Permissions;

        //TODO: get this from session
        $this->auth_token = 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJhdXRoIiwic3ViIjoiMTIzNDU2Nzg5MCIsIm5hbWUiOiJKb2huIERvZSIsImFkbWluIjp0cnVlfQ.DN5nRerbLPuKDf_--HgKqQ_OKWZWztn6n0zWkUsaCsZf3JxyHyzkpL_wWKOL2UMUQl0sh2TCMX0zJ1LE24fz5uVkEZXGIzaQv7513p30EbW7CTXrWkB6rE01IqrMUgDwKa27hdELlRE727fEq3nFlbPPIMJcEebUBZUR2IivGvw';

        $this->caller = new Client([
            'headers' => [
                'Accept' 	=> 'application/json',
                'Host'     	=> $this->config['host'],
            ],
        ]);
    }


    /**
     * @return mixed
     */
    protected function getPermissions()
    {
        return $this->permissions->authorize($this->issuer, $this->auth_token);
    }


    /**
     * @return string
     */
    protected function getToken()
    {
        $permissions = $this->getPermissions();

        $payload = Payload::createPayload([
            'iss' => $this->config['issuer'],
            'perms' => $permissions,
        ]);

        $token = JWT::encode($payload, $this->config['private_key']);

        return $token;
    }

    /**
     * @param string $method
     * @param string $url
     * @param        $data
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    protected function call(string $method, string $url, $data = null)
    {
        $url = $this->config['endpoint'] . $url;

        $data['headers']['Authorization'] = 'X-Memento-Key ' . $this->getToken();

        $response = $this->caller->request($method, $url, $data);

        return json_decode($response->getBody(), true);
    }


    /**
     * @param $url
     * @param $data
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    public function post($url, $data)
    {
        return $this->call('POST', $url, $data);
    }

    /**
     * @param $url
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    public function get($url)
    {
        return $this->call('GET', $url);
    }

    /**
     * @param $url
     * @param $data
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    public function put($url, $data)
    {
        return $this->call('PUT', $url, $data);
    }

    /**
     * @param $url
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    public function delete($url)
    {
        return $this->call('DELETE', $url);
    }

}