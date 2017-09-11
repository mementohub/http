<?php

namespace iMemento\Http;

use iMemento\JWT\Issuer;
use iMemento\JWT\JWT;
use iMemento\JWT\Payload;
use GuzzleHttp\Client;

/**
 * Class Permissions
 *
 * @package iMemento\Http
 */
class Permissions
{

    /**
     * @var Issuer
     */
    protected $issuer;

    /**
     * @var array
     */
    protected $config = [
        //'endpoint' => 'http://api-permissions.dev/api/v1/authorize',
        //'host' => 'api-permissions.dev',
        'endpoint' => 'http://api.imemento.com:8000/api/v1/authorize',
        'host' => 'api.perms.imemento.com',
    ];

    /**
     * Permissions constructor.
     *
     * @param Issuer $issuer
     * @param array  $config
     */
    public function __construct(Issuer $issuer, array $config = [])
    {
        $this->issuer = $issuer;
        $this->config = array_merge($this->config, $config);
    }

    /**
     * @param string|null $user_token
     * @param string      $service_id
     * @return mixed
     */
    public function authorize(string $user_token = null, string $service_id)
    {
        $payload = Payload::create([
            'iss' => $this->issuer->name,
            'srv' => $service_id,
            'user' => $user_token,
        ]);

        $token = JWT::encode($payload, $this->issuer->private_key);

        $client = new Client([
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ],
        ]);

        $response = $client->request('POST', $this->config['endpoint'], ['headers' => ['Host' => $this->config['host']]]);

        return json_decode($response->getBody());
    }

}