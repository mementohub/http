<?php

namespace iMemento\Http;

use iMemento\JWT\Issuer;
use iMemento\JWT\JWT;
use iMemento\JWT\Payload;
use GuzzleHttp\Client;

class Permissions
{

    /*protected $config = [];

    public function __construct($config)
    {
        $this->config = array_merge($this->config, $config);
    }*/

    public function authorize(Issuer $issuer, string $user_token = null)
    {
        $payload = Payload::createPayload([
            'iss' => $issuer->name,
            'user_token' => $user_token,
        ]);

        $token = JWT::encode($payload, $issuer->private_key);

        $client = new Client([
            'headers' => [
                'Accept' => 'application/json',
                'Host' => 'api-permissions.dev',
                'Authorization' => 'X-Memento-Key ' . $token,
            ],
        ]);

        $response = $client->request('POST', 'http://api-permissions.dev/api/v1/authorize');

        //TODO: could get rid of decode / encode
        $permissions = json_decode($response->getBody(), true);

        return $permissions;
    }

}