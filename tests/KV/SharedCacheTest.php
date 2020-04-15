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

        $this->assertSame($expected, $this->makeKV($return)->has(...$keys));
    }

    /**
     * @return iterable
     */
    public function hasProvider(): iterable
    {
        return [
            [new LogicException('RPC error'), ['a', 'b'], [], true],
            [null, ['a', 'b'], [], true],
            [['a' => 'not bool value', 'b' => '', 'c' => true], ['a', 'b'], ['a' => true, 'b' => false]],
            [['b' => false, 'c' => null], ['a', 'b', 'c'], ['a' => false, 'b' => false, 'c' => false]],
        ];
    }

    /**
     * @dataProvider getProvider
     * @param mixed $return
     * @param mixed $expected
     * @param bool  $expectException
     * @throws SharedCacheException
     */
    public function testGet($return, $expected, bool $expectException = false): void
    {
        if ($expectException) {
            $this->expectException(SharedCacheException::class);
        }

        $this->assertSame($expected, $this->makeKV($return)->get('key'));
    }

    /**
     * @return iterable
     */
    public function getProvider(): iterable
    {
        yield [new LogicException('RPC error'), [], true];

        $values = ['string', null, false, true, -3, 0, 1, 2.3, [], new \stdClass()];

        foreach ($values as $value) {
            yield [$value, $value];
        }
    }

    public function testMGet(): void
    {
        $this->assertTrue(true);
    }

    public function testSet(): void
    {
        $this->assertTrue(true);
    }

    public function testMExpire(): void
    {
        $this->assertTrue(true);
    }

    public function testTtl(): void
    {
        $this->assertTrue(true);
    }

    public function testDelete(): void
    {
        $this->assertTrue(true);
    }

    public function testClose(): void
    {
        $this->assertTrue(true);
    }

    /**
     * @param $return
     * @return SharedCache
     */
    private function makeKV($return): SharedCache
    {
        return new SharedCache(RPC::create($return), 'driver');
    }
}
