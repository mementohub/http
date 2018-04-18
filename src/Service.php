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
     * @param string $method
     * @param string $url
     * @param array  $data
     * @param array  $config
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    protected function call(string $method, string $url, array $data = [], array $config = [])
    {
        $whole = $config['whole'] ?? false;
        $data = array_merge($config, $data);

        try {
            $response = $this->client->request($method, $url, $data);
            return $whole ? $response : json_decode($response->getBody()->getContents(), true);
        } catch (ClientException $e) {

            $status = $e->getResponse()->getStatusCode();

            //todo manage error responses here

            //if the service returns 401, we check the error code
            /*if ($status == 401) {
                $code = json_decode($e->getResponse()->getBody())->code;
                if (in_array($code, [1002, 1003, 1004])) {
                    $this->handleTokensRefresh($code, $method, $url, $data);
                } else {
                    throw new InvalidPermissionsException('Unauthorized.');
                }
            //if 403 Forbidden
            } elseif ($status == 403) {
                throw new InvalidPermissionsException('Forbidden.');
            }*/
        }
    }

    /**
     * @param string $url
     * @param array  $query
     * @param array  $config
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    protected function _get(string $url, array $query = null, array $config = [])
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
    protected function _post(string $url, array $body = null, array $config = [])
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
    protected function _put(string $url, array $body = null, array $config = [])
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
    protected function _delete(string $url, array $config = [])
    {
        return $this->call('DELETE', $url, $config);
    }

}