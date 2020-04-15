<?php

declare(strict_types=1);

namespace Spiral\KV\Tests;

use PHPUnit\Framework\TestCase;
use Spiral\KV\Item;
use Spiral\KV\SharedCache;
use Spiral\KV\Tests\Fixture\RPC;

class TestSharedCache extends TestCase
{
    public function testVariadic(): void
    {
        $kv = new SharedCache(RPC::create(), 'driver');

        $kv->has('k1', 'k2', 'k3');
        $kv->set(Item::create('k1', 1), Item::withTTL('k2', 'v2', new \DateTime()));
        $this->assertTrue(true);
    }
}
