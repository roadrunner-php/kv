<?php

declare(strict_types=1);

namespace Spiral\KV\Tests;

use DateTimeImmutable;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;
use Spiral\Core\Exception\LogicException;
use Spiral\KV\Item;
use Spiral\KV\Packer;
use Spiral\KV\SharedCache;
use Spiral\KV\SharedCacheException;
use Spiral\KV\Tests\Fixture\ExpectedResponseRPC;

class SharedCacheTest extends TestCase
{
    /**
     * @dataProvider hasProvider
     * @param mixed $return
     * @param array $expected
     * @param bool  $expectException
     * @throws SharedCacheException
     */
    public function testHas($return, array $expected, bool $expectException = false): void
    {
        if ($expectException) {
            $this->expectException(SharedCacheException::class);
        }

        $this->assertSame($expected, $this->makeKV($return)->has('a', 'b'));
    }

    /**
     * @return iterable
     */
    public function hasProvider(): iterable
    {
        return [
            [new LogicException('RPC error'), [], true],
            [null, [], true],
            [['a' => 'not bool value', 'b' => '', 'c' => true], ['a' => true, 'b' => false]],
            [['b' => false, 'c' => null], ['a' => false, 'b' => false]],
        ];
    }

    /**
     * @dataProvider getProvider
     * @param mixed $return
     * @param array $expected
     * @param bool  $expectException
     */
    public function testGet($return, array $expected, bool $expectException = false): void
    {
        if ($expectException) {
            $this->expectException(SharedCacheException::class);
        }

        $this->assertSame($expected, $this->makeKV($return)->get('a', 'b'));
    }

    /**
     * @return iterable
     */
    public function getProvider(): iterable
    {
        return [
            [new LogicException('RPC error'), [], true],
            [null, [], true],
            [['a' => 'value', 'b' => '', 'c' => true], ['a' => 'value', 'b' => '']],
            [['b' => false, 'c' => null], ['a' => null, 'b' => false]],
        ];
    }

    /**
     * @dataProvider voidCallProvider
     * @param mixed $return
     * @param bool  $expectException
     * @throws SharedCacheException
     */
    public function testSet($return, bool $expectException = false): void
    {
        $this->assertTrue(true);
        if ($expectException) {
            $this->expectException(SharedCacheException::class);
        }

        $this->makeKV($return)->set(
            Item::create('key', 'value'),
            Item::create('another key', 'value', new DateTimeImmutable())
        );
    }

    /**
     * @dataProvider voidCallProvider
     * @param mixed $return
     * @param bool  $expectException
     * @throws SharedCacheException
     */
    public function testExpire($return, bool $expectException = false): void
    {
        $this->assertTrue(true);
        if ($expectException) {
            $this->expectException(SharedCacheException::class);
        }

        $this->makeKV($return)->expire(Item::create('key', 'value'), Item::ttl('key2'));
    }

    /**
     * @dataProvider ttlProvider
     * @param mixed $return
     * @param array $expected
     * @param bool  $expectException
     * @throws SharedCacheException
     */
    public function testTTL($return, array $expected, bool $expectException = false): void
    {
        if ($expectException) {
            $this->expectException(SharedCacheException::class);
        }

        $this->assertSame(
            array_map(static function (?DateTimeInterface $date) {
                return $date !== null ? $date->format(DATE_RFC3339) : null;
            }, $expected),
            array_map(static function (?DateTimeInterface $date) {
                return $date !== null ? $date->format(DATE_RFC3339) : null;
            }, $this->makeKV($return)->ttl('a', 'b'))
        );
    }

    /**
     * @return iterable
     */
    public function ttlProvider(): iterable
    {
        return [
            [new LogicException('RPC error'), [], true],
            [null, [], true],
            [['b' => false, 'c' => null], ['a' => null, 'b' => null]],
            [
                [
                    'a' => 'not bool value',
                    'b' => DateTimeImmutable::createFromFormat(DATE_RFC3339, '2020-04-15T12:00:27+00:20'),
                    'c' => true
                ],
                [
                    'a' => null,
                    'b' => DateTimeImmutable::createFromFormat(DATE_RFC3339, '2020-04-15T12:00:27+00:20')
                ]
            ],
            [
                [
                    'a' => 'not bool value',
                    'b' => 1234567890,
                    'c' => true
                ],
                [
                    'a' => null,
                    'b' => DateTimeImmutable::createFromFormat(DATE_RFC3339, '2009-02-13T23:31:30+00:00')
                ]
            ],
            [
                [
                    'a' => 'not bool value',
                    'b' => 1234567890.123,
                    'c' => true
                ],
                [
                    'a' => null,
                    'b' => null
                ]
            ],
        ];
    }

    /**
     * @dataProvider voidCallProvider
     * @param mixed $return
     * @param bool  $expectException
     * @throws SharedCacheException
     */
    public function testDelete($return, bool $expectException = false): void
    {
        $this->assertTrue(true);
        if ($expectException) {
            $this->expectException(SharedCacheException::class);
        }

        $this->makeKV($return)->delete('a', 'b');
    }

    /**
     * @return iterable
     */
    public function voidCallProvider(): iterable
    {
        return [
            [new LogicException('RPC error'), true],
            [null],
        ];
    }

    /**
     * @param $return
     * @return SharedCache
     */
    private function makeKV($return): SharedCache
    {
        return new SharedCache(ExpectedResponseRPC::create($return), new Packer(), 'driver');
    }
}
