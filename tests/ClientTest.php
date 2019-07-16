<?php

namespace Tests;

use Aziev\MemcachedClient\Client;
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
        $this->key = md5(microtime(true));
        $this->value = sha1($this->key);
        $this->client = new Client($this->host);
    }

    /**
     * Test the set method.
     *
     * @return void
     */
    public function testSet()
    {
        $this->assertTrue($this->client->set($this->key, $this->value));
    }

    /**
     * Test the get method.
     *
     * @return void
     */
    public function testGet()
    {
        // Test for existing key
        $this->client->set($this->key, $this->value);
        $this->assertEquals(
            $this->value,
            $this->client->get($this->key)
        );

        // Test for not existing key
        $this->expectException(\Exception::class);
        $this->client->get(md5($this->key));
    }

    /**
     * Test the delete method.
     *
     * @return void
     */
    public function testDelete()
    {
        $this->client->set($this->key, $this->value);

        $this->assertEquals(
            $this->value,
            $this->client->get($this->key)
        );

        $this->client->delete($this->key);
        $this->expectException(\Exception::class);
        $this->client->get($this->key);
    }

    public function testAsync()
    {
        $this->client->async();
        $this->assertTrue($this->client->isAsync());

        $this->client->async(false);
        $this->assertFalse($this->client->isAsync());

        $this->expectException(\InvalidArgumentException::class);
        $this->client->async('not boolean');
    }

    /**
     * Cleanup after test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        try {
            $this->client->delete($this->key);
        } catch (\Exception $e) {
            //
        }
    }
}
