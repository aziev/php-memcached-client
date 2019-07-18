<?php

declare(strict_types=1);

namespace Aziev\MemcachedClient;

use Aziev\MemcachedClient\Exceptions\NoValueFoundForTheKeyException;
use Exception;

class Client
{
    const RESPONSE_VALUE = 'VALUE';

    const RESPONSE_STORED = 'STORED';

    const RESPONSE_DELETED = 'DELETED';

    const RESPONSE_NOT_FOUND = 'NOT_FOUND';

    const RESPONSE_END = 'END';

    const RESPONSE_OK = 'OK';

    const RESPONSE_EXISTS = 'EXISTS';

    const RESPONSE_ERROR = 'ERROR';

    const RESPONSE_RESET = 'RESET';

    const RESPONSE_NOT_STORED = 'NOT_STORED';

    const RESPONSE_VERSION = 'VERSION';

    const LINE_ENDINGS = "\r\n";

    const COMMAND_SET = 'set';

    const COMMAND_GET = 'get';

    const COMMAND_DELETE = 'delete';

    private $host;

    private $port;

    private $timeout;

    private $connection = null;

    private $asyncMode = false;

    /**
     * Memcached server ending responses list.
     *
     * @var array
     */
    private $endResponses = [
        self::RESPONSE_END,
        self::RESPONSE_DELETED,
        self::RESPONSE_NOT_FOUND,
        self::RESPONSE_OK,
        self::RESPONSE_EXISTS,
        self::RESPONSE_ERROR,
        self::RESPONSE_RESET,
        self::RESPONSE_STORED,
        self::RESPONSE_NOT_STORED,
        self::RESPONSE_VERSION,
    ];

    public function __construct(string $host = 'localhost', int $port = 11211, int $timeout = 30)
    {
        $this->host = $host;
        $this->port = $port;
        $this->timeout = $timeout;
    }

    public function __destruct()
    {
        if ($this->connection) {
            fclose($this->connection);
        }
    }

    /**
     * Switch async mode
     *
     * @param bool $value
     */
    public function async(bool $value = true): void
    {
        $this->asyncMode = $value;
    }

    /**
     * Check if async mode is switched on
     *
     * @return bool
     */
    public function isAsync(): bool
    {
        return $this->asyncMode;
    }

    /**
     * Execute the memcached command.
     *
     * @param string $command
     * @param bool $forceSync
     *
     * @throws Exception
     *
     * @return string
     */
    private function execute(string $command, bool $forceSync = false): string
    {
        $connection = $this->getConnection();
        $input = $command . self::LINE_ENDINGS;
        $output = '';

        fwrite($connection, $input);

        if ($this->asyncMode && !$forceSync) {
            return 'QUEUED';
        }

        while (!feof($connection)) {
            $output .= fgets($connection, 256);

            foreach ($this->endResponses as $item) {
                if (preg_match('/^' . $item . '/imu', $output)) {
                    break 2;
                }
            }
        }

        return $output;
    }

    /**
     * Get instance of connection to Memcached server.
     *
     * @throws Exception
     *
     * @return resource
     */
    private function getConnection()
    {
        if ($this->connection) {
            return $this->connection;
        }

        $connection = fsockopen($this->host, $this->port, $errorNumber, $errorString, $this->timeout);

        if (!$connection) {
            throw new Exception(sprintf(
                'Error "%s: %s" while connecting to Memcached on host: %s:%s',
                $errorNumber,
                $errorString,
                $this->host,
                $this->port
            ));
        }

        return $connection;
    }

    /**
     * Check if Memcached response status matches passed parameter
     *
     * @param string $response
     * @param string $status
     *
     * @return bool
     */
    private function isResponseStatus(string $response, string $status): bool
    {
        return $status === substr($response, 0, strlen($status));
    }

    /**
     * Set value for the specified key.
     *
     * @param string $key
     * @param string $value
     * @param int $expirationTime
     *
     * @throws Exception
     *
     * @return bool
     */
    public function set(string $key, string $value, int $expirationTime = 3600): bool
    {
        $valueLength = strlen($value);
        $result = $this->execute(
            self::COMMAND_SET . " {$key} 0 {$expirationTime} {$valueLength}" . self::LINE_ENDINGS
            . $value
        );

        if (!$this->isResponseStatus($result, self::RESPONSE_STORED)) {
            throw new Exception("Error when trying to set value: {$value} for the key: {$key}");
        }

        return true;
    }

    /**
     * Get value by the key.
     *
     * @param string $key
     *
     * @throws Exception
     *
     * @return string
     */
    public function get(string $key): string
    {
        $result = $this->execute(self::COMMAND_GET . " {$key}", true);

        if (!$this->isResponseStatus($result, self::RESPONSE_VALUE)) {
            throw new Exception("Error when trying to get value for the key: {$key}");
        }

        if ($this->isResponseStatus($result, self::RESPONSE_END)) {
            throw new Exception("No value found for the key: {$key}");
        }

        return explode(self::LINE_ENDINGS, $result)[1];
    }

    /**
     * Delete value by the key.
     *
     * @param string $key
     *
     * @throws NoValueFoundForTheKeyException
     * @throws Exception
     *
     * @return bool
     */
    public function delete(string $key): bool
    {
        $result = $this->execute(self::COMMAND_DELETE . " {$key}");

        if ($this->isResponseStatus($result, self::RESPONSE_NOT_FOUND)) {
            throw new NoValueFoundForTheKeyException("No value found with key: {$key}");
        }

        if (!$this->isResponseStatus($result, self::RESPONSE_DELETED)) {
            throw new Exception("Error when trying to delete value for the key: {$key}");
        }

        return true;
    }

    /**
     * Delete value by the key if it exists.
     *
     * @param string $key
     *
     * @throws Exception
     *
     * @return bool
     */
    public function deleteIfExists(string $key): bool
    {
        try {
            $this->delete($key);
        } catch (NoValueFoundForTheKeyException $e) {
            return true;
        }

        return true;
    }
}
