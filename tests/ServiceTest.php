<?php

namespace iMemento\Http;

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;

/**
 * @covers Client
 */
class ServiceTest extends TestCase
{
    public static $mock;
    public static $handler;
    public static $client;

    public static function setUpBeforeClass()
    {
        self::$mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar']),
            new Response(202, [], json_encode(['test' => true])),
            new RequestException("Error Communicating with Server", new Request('GET', 'test'))
        ]);
        self::$handler = HandlerStack::create(self::$mock);
        self::$client = new Client(['handler' => self::$handler]);
    }

    public function test_get_request()
    {
        $stub = $this->getMockForAbstractClass(
            Service::class, [
                'base_uri' => 'test',
                'token' => 'test',
        ]);
        $stub->setClient(self::$client);

        $status = $stub->get('/')->getStatusCode();

        $this->assertEquals(200, $status);
    }

    public function test_post_request()
    {
        $stub = $this->getMockForAbstractClass(
            Service::class, [
            'base_uri' => 'test',
            'token' => 'test',
        ]);
        $stub->setClient(self::$client);

        $response = $stub->post('/');
        $data = json_decode($response->getBody()->getContents());

        $this->assertEquals(202, $response->getStatusCode());
        $this->assertObjectHasAttribute('test', $data);
    }

    public function test_request_exception()
    {
        $this->expectException(RequestException::class);

        $stub = $this->getMockForAbstractClass(
            Service::class, [
            'base_uri' => 'test',
            'token' => 'test',
        ]);
        $stub->setClient(self::$client);
        $stub->post('/');
    }

}