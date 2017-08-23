<?php

namespace iMemento\Http;

use GuzzleHttp\Client;

/**
 * Class Service
 *
 * @package iMemento\Http
 */
class Service {

    /**
     * @var string
     */
    private $endpoint = 'http://endpoint.kong';
    /**
     * @var string
     */
    private $host = 'example.com';
    /**
     * @var string
     */
    private $private_key = 'key/location';
    /**
     * @var string
     */
    private $issuer = 'this-app';

    /**
     * Service constructor.
     *
     * @param $endpoint
     * @param $host
     * @param $private_key
     * @param $issuer
     */
    public function __construct($endpoint, $host, $private_key, $issuer) {
        $this->endpoint = $endpoint;
        $this->host = $host;
        $this->private_key = $private_key;
        $this->issuer = $issuer;

        $this->caller = new Client([
            'headers' => [
                'Accept' 	=> 'application/json',
                'Host'     	=> $this->host,
            ],
        ]);
    }


    protected function getPermissions()
    {
        
    }


    /**
     * @return string
     */
    protected function getToken() {

        return 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiYWRtaW4iOnRydWV9.EkN-DOsnsuRjRO6BxXemmJDm3HbxrbRzXglbN2S4sOkopdU4IsDxTI8jO19W_A4K8ZPJijNLis4EZsHeY559a4DFOd50_OqgHGuERTqYZyuhtF39yxJPAjUESwxk2J5k_4zM3O-vtd1Ghyo4IbqKKSy6J9mTniYJPenn5-HIirE';

    }

    /**
     * @param string $method
     * @param string $url
     * @param        $data
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    private function call(string $method, string $url, $data) {
        $data['headers']['Authorization'] = 'Bearer ' . $this->getToken();

        return $this->caller->request($method, $url, $data);
    }


    /**
     * @param $url
     * @param $data
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    public function post($url, $data) {
        return $this->call('POST', $url, $data);
    }

    /**
     * @param $url
     * @param $data
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    public function get($url, $data) {
        return $this->call('GET', $url, $data);
    }

    /**
     * @param $url
     * @param $data
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    public function put($url, $data) {
        return $this->call('PUT', $url, $data);
    }

    /**
     * @param $url
     * @param $data
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    public function delete($url, $data) {
        return $this->call('DELETE', $url, $data);
    }

}