<?php

declare(strict_types=1);

namespace Spiral\KV\Tests;

use Spiral\KV\Exception\StorageException;
use Spiral\KV\Internal\Packer;
use Spiral\KV\Item;
use Spiral\KV\Cache;
use Spiral\KV\Tests\Fixture\ExpectedResponseRPC;
use Spiral\KV\Tests\Fixture\NullRelay;

class StorageTestCase extends TestCase
{
    /**
     * @dataProvider hasProvider
     * @param mixed $return
     * @param array $expected
     * @param bool  $expectException
     * @throws StorageException
     */
    public function testHas($return, array $expected, bool $expectException = false): void
    {
        if ($expectException) {
            $this->expectException(StorageException::class);
        }

        $this->assertSame($expected, $this->makeKV($return)->has('a', 'b'));
    }

    /**
     * @return iterable
     */
    public function hasProvider(): iterable
    {
        return [
            [new \LogicException('RPC error'), [], true],
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
            $this->expectException(StorageException::class);
        }

        $this->assertSame($expected, $this->makeKV($return)->get('a', 'b'));
    }

    /**
     * @return iterable
     */
    public function getProvider(): iterable
    {
        return [
            [new \LogicException('RPC error'), [], true],
            [null, [], true],
            [['a' => 'value', 'b' => '', 'c' => true], ['a' => 'value', 'b' => '']],
            [['b' => false, 'c' => null], ['a' => null, 'b' => false]],
        ];
    }

    /**
     * @dataProvider voidCallProvider
     * @param mixed $return
     * @param bool  $expectException
     * @throws StorageException
     */
    public function testSet($return, bool $expectException = false): void
    {
        $this->assertTrue(true);
        if ($expectException) {
            $this->expectException(StorageException::class);
        }

        $this->makeKV($return)->set(
            Item::create('key', 'value'),
            Item::create('another key', 'value', new \DateTimeImmutable())
        );
    }

    /**
     * @dataProvider voidCallProvider
     * @param mixed $return
     * @param bool  $expectException
     * @throws StorageException
     */
    public function testExpire($return, bool $expectException = false): void
    {
        $this->assertTrue(true);
        if ($expectException) {
            $this->expectException(StorageException::class);
        }

        $this->makeKV($return)->expire(Item::create('key', 'value'), Item::ttl('key2'));
    }

    /**
     * @dataProvider ttlProvider
     * @param mixed $return
     * @param array $expected
     * @param bool  $expectException
     * @throws StorageException
     */
    public function testTTL($return, array $expected, bool $expectException = false): void
    {
        if ($expectException) {
            $this->expectException(StorageException::class);
        }

        $this->assertSame(
            array_map(static function (?\DateTimeInterface $date) {
                return $date !== null ? $date->format(DATE_RFC3339) : null;
            }, $expected),
            array_map(static function (?\DateTimeInterface $date) {
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
            [new \LogicException('RPC error'), [], true],
            [null, [], true],
            [['b' => false, 'c' => null], ['a' => null, 'b' => null]],
            [
                [
                    'a' => 'not bool value',
                    'b' => \DateTimeImmutable::createFromFormat(DATE_RFC3339, '2020-04-15T12:00:27+00:20'),
                    'c' => true
                ],
                [
                    'a' => null,
                    'b' => \DateTimeImmutable::createFromFormat(DATE_RFC3339, '2020-04-15T12:00:27+00:20')
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
                    'b' => \DateTimeImmutable::createFromFormat(DATE_RFC3339, '2009-02-13T23:31:30+00:00')
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
     * @throws StorageException
     */
    public function testDelete($return, bool $expectException = false): void
    {
        $this->assertTrue(true);
        if ($expectException) {
            $this->expectException(StorageException::class);
        }

        $this->makeKV($return)->delete('a', 'b');
    }

    /**
     * @return iterable
     */
    public function voidCallProvider(): iterable
    {
        return [
            [new \LogicException('RPC error'), true],
            [null],
        ];
    }

    /**
     * @param $return
     * @return Cache
     */
    private function makeKV($return): Cache
    {
        return new Cache(ExpectedResponseRPC::mock($return), new Packer(), 'driver');
    }
}
