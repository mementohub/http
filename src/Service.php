<?php

namespace iMemento\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

/**
 * Class Service
 *
 * @package iMemento\Http
 */
abstract class Service
{

    /**
     * @var
     */
    protected $client;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var
     */
    protected $token;

    /**
     * @var
     */
    protected $base_uri;

    /**
     * Service constructor.
     *
     * @param string $base_uri
     * @param string $token
     * @param array  $config
     */
    public function __construct(string $base_uri, string $token, array $config = [])
    {
        $this->base_uri = $base_uri;
        $this->token = $token;
        //$this->config = array_merge($this->config, $config); todo not used for now

        $this->client = new Client([
            'base_uri' => $this->base_uri,
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $this->token,
            ]
        ]);

    }

    /**
     * @param Client $client
     * @return $this
     */
    public function setClient(Client $client)
    {
        $this->client = $client;
        return $this;
    }

    /**
     * @param string $method
     * @param string $url
     * @param array  $data
     * @param array  $config
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    protected function call(string $method, string $url, array $data = [], array $config = [])
    {
        $data = array_merge($config, $data);

        try {
            return $this->client->request($method, $url, $data);
        } catch (ClientException $e) {
            return $e->getResponse()->getStatusCode();
        }
    }

    /**
     * @param string $url
     * @param array  $query
     * @param array  $config
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    public function get(string $url, array $query = null, array $config = [])
    {
        $data = $query ? ['query' => $query] : [];

        return $this->call('GET', $url, $data, $config);
    }

    /**
     * @param string     $url
     * @param array|null $body
     * @param array      $config
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    public function post(string $url, array $body = null, array $config = [])
    {
        $data = $body ? ['json' => $body] : [];

        //todo handle form_params and multipart

        return $this->call('POST', $url, $data, $config);
    }

    /**
     * @param string     $url
     * @param array|null $body
     * @param array      $config
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    public function put(string $url, array $body = null, array $config = [])
    {
        $data = $body ? ['json' => $body] : [];
        
        //todo handle form_params and multipart

        return $this->call('PUT', $url, $data, $config);
    }

    /**
     * @param string $url
     * @param array  $config
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    public function delete(string $url, array $config = [])
    {
        return $this->call('DELETE', $url, $config);
    }

}