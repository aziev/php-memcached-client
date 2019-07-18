<?php

namespace Tests;

use Aziev\MemcachedClient\Client;
use Aziev\MemcachedClient\Exceptions\NoValueFoundForTheKeyException;
use Exception;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    /**
     * The host used for testing.
     *
     * @var string
     */
    protected $host = '127.0.0.1';

    /**
     * Client instance.
     *
     * @var \Aziev\MemcachedClient\Client
     */
    protected $client;

    /**
     * Key for test entries.
     *
     * @var string
     */
    protected $key;

    /**
     * Value for test entries.
     *
     * @var string
     */
    protected $value;

    /**
     * Prepare some stuff.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->key = md5((string)microtime(true));
        $this->value = sha1($this->key);
        $this->client = new Client($this->host);
    }

    /**
     * Test the set method.
     *
     * @throws Exception
     *
     * @return void
     */
    public function testSet(): void
    {
        $this->assertTrue($this->client->set($this->key, $this->value));
    }

    /**
     * Test the get method.
     *
     * @throws Exception
     *
     * @return void
     */
    public function testGet(): void
    {
        // Test for existing key
        $this->client->set($this->key, $this->value);
        $this->assertEquals(
            $this->value,
            $this->client->get($this->key)
        );

        // Test for not existing key
        $this->expectException(Exception::class);
        $this->client->get(md5($this->key));
    }

    /**
     * Test the delete method.
     *
     * @throws NoValueFoundForTheKeyException
     * @throws Exception
     *
     * @return void
     */
    public function testDelete(): void
    {
        $this->client->set($this->key, $this->value);

        $this->assertEquals(
            $this->value,
            $this->client->get($this->key)
        );

        $this->client->delete($this->key);
        $this->expectException(Exception::class);
        $this->client->get($this->key);

        $this->expectException(NoValueFoundForTheKeyException::class);
        $this->client->delete(md5($this->key));
    }

    /**
     * Test the async method
     */
    public function testAsync(): void
    {
        $this->client->async();
        $this->assertTrue($this->client->isAsync());

        $this->client->async(false);
        $this->assertFalse($this->client->isAsync());
    }

    /**
     * Cleanup after test.
     *
     * @throws Exception
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->client->deleteIfExists($this->key);
    }
}
