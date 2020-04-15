<?php

declare(strict_types=1);

namespace Spiral\KV\Tests;

use PHPUnit\Framework\TestCase;
use Spiral\Core\Exception\LogicException;
use Spiral\KV\SharedCache;
use Spiral\KV\SharedCacheException;
use Spiral\KV\Tests\Fixture\RPC;

class SharedCacheTest extends TestCase
{
    /**
     * @dataProvider hasProvider
     * @param mixed $return
     * @param array $keys
     * @param array $expected
     * @param bool  $expectException
     * @throws SharedCacheException
     */
    public function testHas($return, array $keys, array $expected, bool $expectException = false): void
    {
        if ($expectException) {
            $this->expectException(SharedCacheException::class);
        }

        $kv = new SharedCache(RPC::create($return), 'driver');

        $this->assertSame($expected, $kv->has(...$keys));
    }

    /**
     * @return iterable
     */
    public function hasProvider(): iterable
    {
        return [
            [new LogicException(), ['a', 'b'], [], true],
            [['a' => '1st value', 'b' => false, 'c' => '?'], ['a', 'b'], ['a' => '1st value', 'b' => false]],
            [['b' => false, 'c' => null], ['a', 'b', 'c'], ['a' => false, 'b' => false, 'c' => null]],
        ];
    }
}
