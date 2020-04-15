<?php

declare(strict_types=1);

namespace Spiral\KV\Tests;

use DateTime;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;
use Spiral\Core\Exception\LogicException;
use Spiral\KV\Item;
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

    /**
     * @dataProvider mGetProvider
     * @param mixed $return
     * @param array $expected
     * @param bool  $expectException
     */
    public function testMGet($return, array $expected, bool $expectException = false): void
    {
        if ($expectException) {
            $this->expectException(SharedCacheException::class);
        }

        $this->assertSame($expected, $this->makeKV($return)->mGet('a', 'b'));
    }

    /**
     * @return iterable
     */
    public function mGetProvider(): iterable
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
            Item::create('key', 1),
            Item::withTTL('another key', 'value', new DateTime())
        );
    }

    /**
     * @dataProvider voidCallProvider
     * @param mixed $return
     * @param bool  $expectException
     * @throws SharedCacheException
     */
    public function testMExpire($return, bool $expectException = false): void
    {
        $this->assertTrue(true);
        if ($expectException) {
            $this->expectException(SharedCacheException::class);
        }

        $this->makeKV($return)->mExpire(new DateTime(), 'a', 'b');
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
                    'b' => DateTime::createFromFormat(DATE_RFC3339, '2020-04-15T12:00:27+00:20'),
                    'c' => true
                ],
                [
                    'a' => null,
                    'b' => DateTime::createFromFormat(DATE_RFC3339, '2020-04-15T12:00:27+00:20')
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
        return new SharedCache(ExpectedResponseRPC::create($return), 'driver');
    }
}
