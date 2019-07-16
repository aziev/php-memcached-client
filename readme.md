# Memcached Client

[![Build Status](https://travis-ci.org/aziev/php-memcached-client.svg?branch=master)](https://travis-ci.org/aziev/php-memcached-client)
[![StyleCI](https://github.styleci.io/repos/197148649/shield?branch=master)](https://github.styleci.io/repos/197148649)

The PHP client for Memcached. **This package created for testing purpose. Using it for real applications can be risky**.

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