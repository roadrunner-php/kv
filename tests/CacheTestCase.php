<?php

/**
 * This file is part of RoadRunner package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\RoadRunner\KeyValue\Tests;

use Spiral\Goridge\RPC\Exception\ServiceException;
use Spiral\RoadRunner\KeyValue\Cache;
use Spiral\RoadRunner\KeyValue\DTO\V1\Item;
use Spiral\RoadRunner\KeyValue\DTO\V1\Request;
use Spiral\RoadRunner\KeyValue\DTO\V1\Response;
use Spiral\RoadRunner\KeyValue\Exception\InvalidArgumentException;
use Spiral\RoadRunner\KeyValue\Exception\KeyValueException;
use Spiral\RoadRunner\KeyValue\Exception\NotImplementedException;
use Spiral\RoadRunner\KeyValue\Exception\SerializationException;
use Spiral\RoadRunner\KeyValue\Exception\StorageException;
use Spiral\RoadRunner\KeyValue\Serializer\DefaultSerializer;
use Spiral\RoadRunner\KeyValue\Serializer\IgbinarySerializer;
use Spiral\RoadRunner\KeyValue\Serializer\SerializerInterface;
use Spiral\RoadRunner\KeyValue\Serializer\SodiumSerializer;
use Spiral\RoadRunner\KeyValue\Tests\Stub\FrozenDateCacheStub;
use Spiral\RoadRunner\KeyValue\Tests\Stub\RawSerializerStub;

class CacheTestCase extends TestCase
{
    /**
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private string $name;

    public function setUp(): void
    {
        $this->name = \bin2hex(\random_bytes(32));
        parent::setUp();
    }

    public function testName(): void
    {
        $driver = $this->cache();

        $this->assertSame($this->name, $driver->getName());
    }

    /**
     * @param array<string, mixed> $mapping
     * @param SerializerInterface|null $serializer
     */
    private function cache(array $mapping = [], SerializerInterface $serializer = null): Cache
    {
        return new Cache($this->rpc($mapping), $this->name, $serializer);
    }

    /**
     * @dataProvider serializersDataProvider
     */
    public function testTtl(SerializerInterface $serializer): void
    {
        [$key, $expected] = [$this->randomString(), $this->now()];

        $driver = $this->cache([
            'kv.TTL' => fn() => $this->response([
                new Item([
                    'key'     => $key,
                    'value'   => $serializer->serialize(null),
                    'timeout' => $expected->format(\DateTimeInterface::RFC3339),
                ]),
            ]),
        ], $serializer);

        $actual = $driver->getTtl($key);

        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }

    private function randomString(int $len = 32): string
    {
        return \bin2hex(\random_bytes($len));
    }

    /**
     * Returns normalized datetime without milliseconds
     *
     * @return \DateTimeInterface
     */
    private function now(): \DateTimeInterface
    {
        $time = (new \DateTime())->format(\DateTimeInterface::RFC3339);

        return \DateTime::createFromFormat(\DateTimeInterface::RFC3339, $time);
    }

    /**
     * @param array<Item> $items
     */
    private function response(array $items = []): string
    {
        return (new Response(['items' => $items]))->serializeToString();
    }

    /**
     * @dataProvider serializersDataProvider
     */
    public function testNoTtl(SerializerInterface $serializer): void
    {
        $driver = $this->cache(['kv.TTL' => $this->response()], $serializer);

        $this->assertNull($driver->getTtl('key'));
    }

    /**
     * @dataProvider serializersDataProvider
     */
    public function testMultipleTtl(SerializerInterface $serializer): void
    {
        $keys = [$this->randomString(), $this->randomString()];
        $expected = $this->now();

        $driver = $this->cache([
            'kv.TTL' => fn() => $this->response([
                new Item([
                    'key'     => $keys[0],
                    'value'   => $serializer->serialize(null),
                    'timeout' => $expected->format(\DateTimeInterface::RFC3339),
                ]),
                new Item([
                    'key'     => $keys[1],
                    'value'   => $serializer->serialize(null),
                    'timeout' => $expected->format(\DateTimeInterface::RFC3339),
                ]),
            ]),
        ], $serializer);

        $actual = $driver->getMultipleTtl($keys);

        foreach ($actual as $key => $time) {
            $this->assertContains($key, $keys);
            $this->assertEquals($expected, $time);
        }
    }

    /**
     * @dataProvider serializersDataProvider
     */
    public function testMultipleTtlWithMissingTime(SerializerInterface $serializer): void
    {
        $keys = [$this->randomString(), $this->randomString(), $this->randomString(), $this->randomString()];
        $expected = $this->now();

        $driver = $this->cache([
            'kv.TTL' => fn() => $this->response([
                new Item([
                    'key'     => $keys[0],
                    'value'   => $serializer->serialize(null),
                    'timeout' => $expected->format(\DateTimeInterface::RFC3339),
                ]),
            ]),
        ], $serializer);

        $actual = $driver->getMultipleTtl($keys);

        foreach ($actual as $key => $time) {
            $this->assertContains($key, $keys);

            $expectedForKey = $key === $keys[0] ? $expected : null;
            $this->assertEquals($expectedForKey, $time);
        }
    }

    /**
     * @dataProvider serializersDataProvider
     */
    public function testTtlWithInvalidResponseKey(SerializerInterface $serializer): void
    {
        $driver = $this->cache([
            'kv.TTL' => fn() => $this->response([
                new Item([
                    'key'     => $this->randomString(),
                    'value'   => $serializer->serialize(null),
                    'timeout' => $this->now()->format(\DateTimeInterface::RFC3339),
                ]),
            ]),
        ], $serializer);

        $this->assertNull($driver->getTtl('__invalid__'));
    }

    /**
     * @return array<string, array{0: callable(Cache)}>
     */
    public function methodsDataProvider(): array
    {
        return [
            'getTtl'         => [fn(Cache $c) => $c->getTtl('key')],
            'getMultipleTtl' => [fn(Cache $c) => $c->getMultipleTtl(['key'])],
            'get'            => [fn(Cache $c) => $c->get('key')],
            'set'            => [fn(Cache $c) => $c->set('key', 'value')],
            'getMultiple'    => [fn(Cache $c) => $c->getMultiple(['key'])],
            'setMultiple'    => [fn(Cache $c) => $c->setMultiple(['key' => 'value'])],
            'deleteMultiple' => [fn(Cache $c) => $c->deleteMultiple(['key'])],
            'delete'         => [fn(Cache $c) => $c->delete('key')],
            'has'            => [fn(Cache $c) => $c->has('key')],
        ];
    }

    /**
     * @param callable(Cache) $handler
     * @dataProvider methodsDataProvider
     */
    public function testBadStorageNameOnAnyMethodExecution(callable $handler): void
    {
        // When RPC ServiceException like
        $error = function () {
            throw new ServiceException('no such storage "' . $this->name . '"');
        };

        // Then expects message like that cache storage has not been defined
        $this->expectException(StorageException::class);
        $this->expectExceptionMessage(\sprintf(
            'Storage "%s" has not been defined. Please make sure your ' .
            'RoadRunner "kv" configuration contains a storage key named "%1$s"',
            $this->name
        ));

        $driver = $this->cache([
            'kv.Has'     => $error,
            'kv.Set'     => $error,
            'kv.MGet'    => $error,
            'kv.MExpire' => $error,
            'kv.TTL'     => $error,
            'kv.Delete'  => $error,
        ]);

        $result = $handler($driver);

        // When the generator returns, then no error occurs
        if ($result instanceof \Generator) {
            \iterator_to_array($result);
        }
    }

    public function testTtlNotAvailable(): void
    {
        // When RPC ServiceException like
        $error = function () {
            throw new ServiceException('memcached_plugin_ttl: ttl not available');
        };

        // Then expects message like that TTL not available
        $this->expectException(NotImplementedException::class);
        $this->expectExceptionMessage(\sprintf(
            'Storage "%s" does not support kv.TTL RPC method execution. Please ' .
            'use another driver for the storage if you require this functionality',
            $this->name
        ));

        $driver = $this->cache(['kv.TTL' => $error]);

        $driver->getTtl('key');
    }

    /**
     * @dataProvider serializersDataProvider
     */
    public function testGet(SerializerInterface $serializer): void
    {
        $expected = $this->randomString(1024);

        $driver = $this->cache([
            'kv.MGet' => $this->response([
                new Item(['key' => 'key', 'value' => $serializer->serialize($expected)]),
            ]),
        ], $serializer);

        $this->assertSame($expected, $driver->get('key'));
    }

    public function testGetWhenValueNotExists(): void
    {
        $driver = $this->cache(['kv.MGet' => $this->response()]);

        $this->assertNull($driver->get('key'));
    }

    public function testGetDefaultWhenValueNotExists(): void
    {
        $expected = $this->randomString();

        $driver = $this->cache(['kv.MGet' => $this->response()]);

        $this->assertSame($expected, $driver->get('key', $expected));
    }

    /**
     * @dataProvider serializersDataProvider
     */
    public function testGetMultiple(SerializerInterface $serializer): void
    {
        $expected = [
            'key0' => $this->randomString(),
            'key1' => $this->randomString(),
            'key2' => null,
            'key3' => null,
        ];

        $driver = $this->cache([
            // Only 2 items of 4 should be returned
            'kv.MGet' => $this->response([
                new Item(['key' => 'key0', 'value' => $serializer->serialize($expected['key0'])]),
                new Item(['key' => 'key1', 'value' => $serializer->serialize($expected['key1'])]),
            ]),
        ], $serializer);

        $actual = $driver->getMultiple(\array_keys($expected));

        $this->assertSame($expected, \iterator_to_array($actual));
    }

    /**
     * @dataProvider serializersDataProvider
     */
    public function testHas(SerializerInterface $serializer): void
    {
        $key = $this->randomString();

        $driver = $this->cache([
            'kv.Has' => $this->response([
                new Item(['key' => $key, 'value' => $serializer->serialize(null)]),
            ]),
        ], $serializer);

        $this->assertTrue($driver->has($key));
    }

    /**
     * @dataProvider serializersDataProvider
     */
    public function testHasWhenNotExists(SerializerInterface $serializer): void
    {
        $key = $this->randomString();

        $driver = $this->cache([
            'kv.Has' => $this->response(),
        ], $serializer);

        $this->assertFalse($driver->has($key));
    }

    /**
     * @dataProvider serializersDataProvider
     */
    public function testHasWithInvalidResponse(SerializerInterface $serializer): void
    {
        $key = $this->randomString();

        $driver = $this->cache([
            'kv.Has' => $this->response([
                new Item(['key' => $key, 'value' => $serializer->serialize(null)]),
            ]),
        ], $serializer);

        $this->assertFalse($driver->has('__invalid_key__'));
    }

    public function testClear(): void
    {
        $driver = $this->cache(['kv.Clear' => $this->response()]);

        $result = $driver->clear();

        $this->assertTrue($result);
    }

    public function testClearError(): void
    {
        $this->expectException(KeyValueException::class);
        $this->expectExceptionMessage('Something went wrong');

        $driver = $this->cache(['kv.Clear' => function () {
            throw new ServiceException('Something went wrong');
        }]);

        $driver->clear();
    }

    public function testClearMethodNotFoundError(): void
    {
        $this->expectException(KeyValueException::class);
        $this->expectExceptionMessage(
            'RoadRunner does not support kv.Clear RPC method. ' .
            'Please make sure you are using RoadRunner v2.3.1 or higher.'
        );

        $driver = $this->cache();
        $driver->clear();
    }

    public function serializersWithValuesDataProvider(): array
    {
        $result = [];

        foreach ($this->serializersDataProvider() as $name => [$serializer]) {
            foreach ($this->valuesDataProvider() as $type => [$value]) {
                $result['[' . $type . '] using [' . $name . ']'] = [$serializer, $value];
            }
        }

        return $result;
    }

    /**
     * @return array<string, array{0: SerializerInterface}>
     * @throws \SodiumException
     */
    public function serializersDataProvider(): array
    {
        $result = [];
        $result['PHP Serialize'] = [new DefaultSerializer()];

        // ext-igbinary required for this serializer
        if (\extension_loaded('igbinary')) {
            $result['Igbinary'] = [new IgbinarySerializer()];
        }

        // ext-sodium required for this serialize
        if (\extension_loaded('sodium')) {
            foreach ($result as $name => [$serializer]) {
                $result['Sodium through ' . $name] = [
                    new SodiumSerializer($serializer, \sodium_crypto_box_keypair()),
                ];
            }
        }

        return $result;
    }

    /**
     * @dataProvider serializersWithValuesDataProvider
     */
    public function testSet(SerializerInterface $serializer, $expected): void
    {
        if (\is_float($expected) && \is_nan($expected)) {
            $this->markTestSkipped('Unable to execute test for NAN float value');
        }

        if (\is_resource($expected)) {
            $this->markTestSkipped('Unable to execute test for resource value');
        }

        $driver = $this->getAssertableCacheOnSet($serializer, ['key' => $expected]);

        $driver->set('key', $expected);
    }

    /**
     * @param SerializerInterface $serializer
     * @param array $expected
     * @return Cache
     */
    private function getAssertableCacheOnSet(SerializerInterface $serializer, array $expected): Cache
    {
        return $this->cache([
            'kv.Set' => function (Request $request) use ($serializer, $expected): string {
                $items = $request->getItems();

                $result = [];

                /** @var Item $item */
                foreach ($items as $item) {
                    $result[] = $item;

                    $this->assertArrayHasKey($item->getKey(), $expected);
                    $this->assertEquals($expected[$item->getKey()], $serializer->unserialize($item->getValue()));
                }

                $this->assertSame($items->count(), \count($expected));

                return $this->response($result);
            },
        ], $serializer);
    }

    /**
     * @dataProvider serializersWithValuesDataProvider
     */
    public function testMultipleSet(SerializerInterface $serializer, $value): void
    {
        if (\is_float($value) && \is_nan($value)) {
            $this->markTestSkipped('Unable to execute test for NAN float value');
        }

        if (\is_resource($value)) {
            $this->markTestSkipped('Unable to execute test for resource value');
        }

        $expected = ['key' => $value, 'key2' => $value];

        $driver = $this->getAssertableCacheOnSet($serializer, $expected);
        $driver->setMultiple($expected);
    }

    public function testSetWithRelativeIntTTL(): void
    {
        $seconds = 0xDEAD_BEEF;

        // This is the current time for cache and relative date
        $now = new \DateTimeImmutable();
        // Relative date: [$now] + [$seconds]
        $expected = $now->add(new \DateInterval("PT{$seconds}S"))
            ->format(\DateTimeInterface::RFC3339);

        $driver = $this->frozenDateCache($now, [
            'kv.Set' => function (Request $request) use ($expected) {
                /** @var Item $item */
                $item = $request->getItems()[0];
                $this->assertSame($expected, $item->getTimeout());

                return $this->response();
            },
        ]);

        // Send relative date in $now + $seconds
        $driver->set('key', 'value', $seconds);
    }

    /**
     * @param array<string, mixed> $mapping
     * @param SerializerInterface|null $serializer
     */
    private function frozenDateCache(
        \DateTimeImmutable $date,
        array $mapping = [],
        SerializerInterface $serializer = null
    ): Cache {
        return new FrozenDateCacheStub($date, $this->rpc($mapping), $this->name, $serializer);
    }

    public function testSetWithRelativeDateIntervalTTL(): void
    {
        $seconds = 0xDEAD_BEEF;
        $interval = new \DateInterval("PT{$seconds}S");

        // This is the current time for cache and relative date
        $now = new \DateTimeImmutable();

        // Add interval to frozen current time
        $expected = $now->add($interval)
            ->format(\DateTimeInterface::RFC3339);

        $driver = $this->frozenDateCache($now, [
            'kv.Set' => function (Request $request) use ($expected) {
                /** @var Item $item */
                $item = $request->getItems()[0];
                $this->assertSame($expected, $item->getTimeout());

                return $this->response();
            },
        ]);

        $driver->set('key', 'value', $interval);
    }

    /**
     * @dataProvider valuesDataProvider
     */
    public function testSetWithInvalidTTL($invalidTTL): void
    {
        $type = \get_debug_type($invalidTTL);

        if ($invalidTTL === null || \is_int($invalidTTL) || $invalidTTL instanceof \DateTimeInterface) {
            $this->markTestSkipped('Can not complete negative test for valid TTL of type ' . $type);
        }

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Cache item ttl (expiration) must be of type int or \DateInterval, but ' . $type . ' passed'
        );

        $driver = $this->cache();

        // Send relative date in $now + $seconds
        $driver->set('key', 'value', $invalidTTL);
    }

    public function testDelete(): void
    {
        $driver = $this->cache(['kv.Delete' => $this->response([])]);
        $this->assertTrue($driver->delete('key'));
    }

    public function testDeleteWithError(): void
    {
        $this->expectException(KeyValueException::class);

        $driver = $this->cache([
            'kv.Delete' => function () {
                throw new ServiceException('Error: Can not delete something');
            },
        ]);

        $driver->delete('key');
    }

    public function testDeleteMultiple(): void
    {
        $driver = $this->cache(['kv.Delete' => $this->response([])]);
        $this->assertTrue($driver->deleteMultiple(['key', 'key2']));
    }

    public function testDeleteMultipleWithError(): void
    {
        $this->expectException(KeyValueException::class);

        $driver = $this->cache([
            'kv.Delete' => function () {
                throw new ServiceException('Error: Can not delete something');
            },
        ]);

        $driver->deleteMultiple(['key', 'key2']);
    }

    public function testGetWithInvalidKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cache key must be a string, but int passed');

        $driver = $this->cache();
        $driver->get(0xDEAD_BEEF);
    }

    public function testGetMultipleWithInvalidKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cache key must be a string, but int passed');

        $driver = $this->cache();
        foreach ($driver->getMultiple([0 => 0xDEAD_BEEF]) as $_) {
            //
        }
    }

    public function testGetMultipleWithInvalidValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cache keys must be an array<string>, but int passed');

        $driver = $this->cache();
        foreach ($driver->getMultiple(0xDEAD_BEEF) as $_) {
        }
    }

    public function testSetWithInvalidKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cache key must be a string, but int passed');

        $driver = $this->cache();
        $driver->set(0xDEAD_BEEF, 'value');
    }

    public function testSetMultipleWithInvalidKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cache key must be a string, but int passed');

        $driver = $this->cache();
        $driver->setMultiple([0 => 0xDEAD_BEEF]);
    }

    public function testSetMultipleWithInvalidValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cache values must be an array<string, mixed>, but int passed');

        $driver = $this->cache();
        $driver->setMultiple(0xDEAD_BEEF);
    }

    public function testDeleteWithInvalidKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cache key must be a string, but int passed');

        $driver = $this->cache();
        $driver->delete(0xDEAD_BEEF);
    }

    public function testDeleteMultipleWithInvalidKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cache key must be a string, but int passed');

        $driver = $this->cache();
        $driver->deleteMultiple([0 => 0xDEAD_BEEF]);
    }

    public function testDeleteMultipleWithInvalidValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cache keys must be an array<string>, but int passed');

        $driver = $this->cache();
        $driver->deleteMultiple(0xDEAD_BEEF);
    }

    public function testHasWithInvalidValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cache key must be a string, but int passed');

        $driver = $this->cache();
        $driver->has(0xDEAD_BEEF);
    }

    public function testImmutableWhileSwitchSerialization(): void
    {
        $expected = $this->randomString(1024);

        $driver = $this->cache([
            'kv.MGet' => $this->response([new Item(['key' => 'key', 'value' => $expected])]),
        ], new RawSerializerStub());

        $decorated = $driver->withSerializer(new DefaultSerializer());

        // Behaviour MUST NOT be changed
        $this->assertSame($expected, $driver->get('key'));
    }

    public function testErrorOnInvalidSerialization(): void
    {
        $this->expectException(SerializationException::class);

        $expected = $this->randomString(1024);

        $driver = $this->cache([
            'kv.MGet' => $this->response([new Item(['key' => 'key', 'value' => $expected])]),
        ], new RawSerializerStub());

        $actual = $driver->withSerializer(new DefaultSerializer())
            ->get('key');
    }
}
