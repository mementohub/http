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

    public function authorize(Issuer $issuer, string $auth_token)
    {
        //TODO: remove the sub, get the user_id from auth_token in api-permissions

        $payload = Payload::createPayload([
            'sub' => 51,
            'iss' => $issuer->name,
            'auth' => $auth_token,
        ]);

        $token = JWT::encode($payload, $issuer->private_key);

        $client = new Client([
            'headers' => [
                'Accept' => 'application/json',
                'Host' => 'api-permissions.dev',
                'Authorization' => 'X-Memento-Key ' . $token,
            ],
        ]);

        $data = [
            'token' => $token,
        ];

        $response = $client->request('POST', 'http://api-permissions.dev/api/v1/authorize', $data);

        //TODO: could get rid of decode / encode
        $permissions = json_decode($response->getBody(), true);

        return $permissions;
    }

}