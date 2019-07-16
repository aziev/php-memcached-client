<?php

namespace Aziev\MemcachedClient;

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

    public function __construct($host = 'localhost', $port = '11211', $timeout = 30)
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

    public function async($value = true)
    {
        if (!is_bool($value)) {
            $type = gettype($value);

            throw new \InvalidArgumentException("Expected boolean value, $type given");
        }

        $this->asyncMode = $value;
    }

    public function isAsync()
    {
        return $this->asyncMode;
    }

    /**
     * Execute the memcached command.
     *
     * @param $command
     * @param bool $forceSync
     *
     * @throws \Exception
     *
     * @return string
     */
    private function execute($command, $forceSync = false)
    {
        $connection = $this->getConnection();
        $input = $command.self::LINE_ENDINGS;
        $output = '';

        fwrite($connection, $input);

        if ($this->asyncMode && !$forceSync) {
            return 'QUEUED';
        }

        while (!feof($connection)) {
            $output .= fgets($connection, 256);

            foreach ($this->endResponses as $item) {
                if (preg_match('/^'.$item.'/imu', $output)) {
                    break 2;
                }
            }
        }

        return $output;
    }

    /**
     * Get instance of connection to Memcached server.
     *
     * @throws \Exception
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
            throw new \Exception(sprintf(
                'Error "%s: %s" while connecting to Memcached on host: %s:%s',
                $errorNumber,
                $errorString,
                $this->host,
                $this->port
            ));
        }

        return $connection;
    }

    private function isResponseStatus($response, $status)
    {
        return $status === substr($response, 0, strlen($status));
    }

    /**
     * Set value for the specified key.
     *
     * @param $key
     * @param $value
     * @param int|string $expirationTime
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function set($key, $value, $expirationTime = 3600)
    {
        $valueLength = strlen($value);
        $result = $this->execute(
            self::COMMAND_SET." $key 0 $expirationTime $valueLength".self::LINE_ENDINGS
            .$value
        );

        if (!$this->isResponseStatus($result, self::RESPONSE_STORED)) {
            throw new \Exception("Error when trying to set value: $value for the key: $key");
        }

        return true;
    }

    /**
     * Get value by the key.
     *
     * @param $key
     *
     * @throws \Exception
     *
     * @return string
     */
    public function get($key)
    {
        $result = $this->execute(self::COMMAND_GET." $key", true);

        if (!$this->isResponseStatus($result, self::RESPONSE_VALUE)) {
            throw new \Exception("Error when trying to get value for the key: $key");
        }

        if ($this->isResponseStatus($result, self::RESPONSE_END)) {
            throw new \Exception("No value found for the key: $key");
        }

        return explode(self::LINE_ENDINGS, $result)[1];
    }

    /**
     * Delete value by the key.
     *
     * @param $key
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function delete($key)
    {
        $result = $this->execute(self::COMMAND_DELETE." $key");

        if ($this->isResponseStatus($result, self::RESPONSE_NOT_FOUND)) {
            throw new \Exception("No value found with key: $key");
        }

        if (!$this->isResponseStatus($result, self::RESPONSE_DELETED)) {
            throw new \Exception("Error when trying to delete value for the key: $key");
        }

        return true;
    }
}
