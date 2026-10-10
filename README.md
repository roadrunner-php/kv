<p align="center">
    <a href="https://roadrunner.dev"><picture>
        <source media="(prefers-color-scheme: dark)" srcset="https://github.com/roadrunner-server/.github/assets/8040338/e6bde856-4ec6-4a52-bd5b-bfe78736c1ff">
        <img alt="RoadRunner" src="https://github.com/roadrunner-server/.github/assets/8040338/040fb694-1dd3-4865-9d29-8e0748c2c8b8" style="width: 6in; display: block">
    </picture></a>
</p>

<p align="center">PSR-16 cache on top of the RoadRunner KV plugin</p>

<div align="center">

[![Documentation](https://img.shields.io/badge/Documentation-blue?style=for-the-badge&logo=gitbook&logoColor=white)](https://docs.roadrunner.dev/docs/key-value/overview-kv)
[![Sponsor](https://img.shields.io/static/v1?style=for-the-badge&label=&message=Sponsor&logo=githubsponsors&logoColor=white&color=%23EA4AAA)](https://github.com/sponsors/roadrunner-server)

[![Psalm Level](https://shepherd.dev/github/roadrunner-php/kv/level.svg)](https://shepherd.dev/github/roadrunner-php/kv)
[![Type Coverage](https://shepherd.dev/github/roadrunner-php/kv/coverage.svg)](https://shepherd.dev/github/roadrunner-php/kv)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Froadrunner-php%2Fkv%2F4.x)](https://dashboard.stryker-mutator.io/reports/github.com/roadrunner-php/kv/4.x)

</div>

<br />

This package lets a PHP application use the storages of the [RoadRunner KV plugin](https://docs.roadrunner.dev/docs/key-value/overview-kv) (memory, boltdb, redis, memcached and others) as a PSR-16 cache, talking to RoadRunner over RPC.

## Get Started

### Installation

```bash
composer require roadrunner/kv
```

[![PHP](https://img.shields.io/packagist/php-v/roadrunner/kv.svg?style=flat-square&logo=php)](https://packagist.org/packages/roadrunner/kv)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/roadrunner/kv.svg?style=flat-square&logo=packagist)](https://packagist.org/packages/roadrunner/kv)
[![License](https://img.shields.io/packagist/l/roadrunner/kv.svg?style=flat-square)](LICENSE)
[![Total Downloads](https://img.shields.io/packagist/dt/roadrunner/kv.svg?style=flat-square)](https://packagist.org/packages/roadrunner/kv/stats)

You can use the convenient installer to download the latest available compatible
version of RoadRunner server:

```bash
composer require roadrunner/cli --dev
vendor/bin/rr get
```

### Configuration

First you need to add at least one kv plugin to your roadrunner configuration. 
For example, such a configuration would be quite feasible to run:

```yaml
rpc:
  listen: tcp://127.0.0.1:6001

kv:
  test:
    driver: memory
    config:
        interval: 10
```

> [!NOTE]
> Read more about all available drivers on the 
> [documentation](https://docs.roadrunner.dev/docs/key-value/overview-kv) page.

After starting the server with this configuration, one driver named "`test`" 
will be available to you.

### Usage

The following code will allow writing and reading an arbitrary value from the 
RoadRunner server.

```php
<?php

use Spiral\Goridge\RPC\RPC;
use Spiral\RoadRunner\KeyValue\Factory;

require __DIR__ . '/vendor/autoload.php';

$factory = new Factory(RPC::create('tcp://127.0.0.1:6001'));

$cache = $factory->select('test');

// After that you can write and read arbitrary values:

$cache->set('key', 'value');

echo $cache->get('key'); // string(5) "value"
```

## Serialization

Values are serialized with PHP's native `serialize()` by default. Pass another serializer to the factory to change that:

- `IgbinarySerializer` — requires the `igbinary` extension;
- `SodiumSerializer` — encrypts values produced by an inner serializer with a keypair from `sodium_crypto_box_keypair()`, requires the `sodium` extension.

```php
use Spiral\RoadRunner\KeyValue\Factory;
use Spiral\RoadRunner\KeyValue\Serializer\DefaultSerializer;
use Spiral\RoadRunner\KeyValue\Serializer\SodiumSerializer;

$factory = new Factory($rpc, new SodiumSerializer(new DefaultSerializer(), $key));
```

<a href="https://spiral.dev/">
<img src="https://user-images.githubusercontent.com/773481/220979012-e67b74b5-3db1-41b7-bdb0-8a042587dedc.jpg" alt="try Spiral Framework" />
</a>
