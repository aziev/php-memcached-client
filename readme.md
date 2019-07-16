# Memcached Client

The PHP client for Memcached.

## Usage

```php
<?php

$client = new \Aziev\MemcachedClient\Client('localhost', '11211');

$client->set('foo', 'bar');
$client->get('foo'); // bar
$client->delete('foo');
```

By default the client work in synchronous mode. But you can let the client work in asynchronous mode:

```php
<?php

// Set async mode
$client->async();

// Set sync mode
$client->async(false);

// Check mode
$client->isAsync();

```