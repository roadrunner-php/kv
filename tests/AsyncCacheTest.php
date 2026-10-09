<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\KeyValue\Tests;

use Testo\Test;
use Testo\Data\DataProvider;
use Testo\Core\Exception\SkipTest;
use Testo\Assert;
use Testo\Expect;
use RoadRunner\KV\DTO\V1\Item;
use RoadRunner\KV\DTO\V1\Request;
use Spiral\Goridge\RPC\Exception\ServiceException;
use Spiral\RoadRunner\KeyValue\AsyncCache;
use Spiral\RoadRunner\KeyValue\Exception\InvalidArgumentException;
use Spiral\RoadRunner\KeyValue\Exception\KeyValueException;
use Spiral\RoadRunner\KeyValue\Serializer\DefaultSerializer;
use Spiral\RoadRunner\KeyValue\Serializer\SerializerInterface;
use Spiral\RoadRunner\KeyValue\Tests\Stub\AsyncFrozenDateCacheStub;

#[Test]
final class AsyncCacheTest extends CacheTestCase
{
    /**
     * @return \Traversable<string, array{0: callable(Cache)}>
     */
    public static function methodsDataProvider(): \Traversable
    {
        yield from parent::methodsDataProvider();

        yield 'setAsync' => [fn(AsyncCache $c) => $c->setAsync('key', 'value') && $c->commitAsync()];
        yield 'setMultipleAsync' => [fn(AsyncCache $c) => $c->setMultiple(['key' => 'value']) && $c->commitAsync()];
        yield 'deleteMultipleAsync' => [fn(AsyncCache $c) => $c->deleteMultipleAsync(['key']) && $c->commitAsync()];
        yield 'deleteAsync' => [fn(AsyncCache $c) => $c->delete('key') && $c->commitAsync()];
    }

    #[DataProvider('serializersWithValuesDataProvider')]
    public function testSetAsync(SerializerInterface $serializer, mixed $expected): void
    {
        if (\is_float($expected) && \is_nan($expected)) {
            throw new SkipTest('Unable to execute test for NAN float value');
        }

        if (\is_resource($expected)) {
            throw new SkipTest('Unable to execute test for resource value');
        }

        $driver = $this->getAssertableCacheOnSet($serializer, ['key' => $expected]);

        $driver->setAsync('key', $expected);
        $driver->commitAsync();
    }

    #[DataProvider('serializersWithValuesDataProvider')]
    public function testMultipleSetAsync(SerializerInterface $serializer, mixed $value): void
    {
        if (\is_float($value) && \is_nan($value)) {
            throw new SkipTest('Unable to execute test for NAN float value');
        }

        if (\is_resource($value)) {
            throw new SkipTest('Unable to execute test for resource value');
        }

        $expected = ['key' => $value, 'key2' => $value];

        $driver = $this->getAssertableCacheOnSet($serializer, $expected);
        $driver->setMultipleAsync($expected);
        $driver->commitAsync();
    }

    public function testSetAsyncWithRelativeIntTTL(): void
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
                Assert::same($item->getTimeout(), $expected);

                return $this->response();
            },
        ]);

        // Send relative date in $now + $seconds
        $driver->setAsync('key', 'value', $seconds);
        $driver->commitAsync();
    }

    public function testSetAsyncWithRelativeDateIntervalTTL(): void
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
                Assert::same($item->getTimeout(), $expected);

                return $this->response();
            },
        ]);

        $driver->setAsync('key', 'value', $interval);
        $driver->commitAsync();
    }

    #[DataProvider('valuesDataProvider')]
    public function testSetAsyncWithInvalidTTL(mixed $invalidTTL): void
    {
        $type = \get_debug_type($invalidTTL);

        if ($invalidTTL === null || \is_int($invalidTTL) || $invalidTTL instanceof \DateTimeInterface) {
            throw new SkipTest('Can not complete negative test for valid TTL of type ' . $type);
        }

        Expect::exception(InvalidArgumentException::class)->withMessageContaining('Cache item ttl (expiration) must be of type int or \DateInterval, but ' . $type . ' passed');

        $driver = $this->cache();

        // Send relative date in $now + $seconds
        $driver->setAsync('key', 'value', $invalidTTL);
        // Make sure not reachable
        Assert::same(false, true);
        $driver->commitAsync();
    }

    public function testDeleteAsync(): void
    {
        $driver = $this->cache(['kv.Delete' => $this->response([])]);
        Assert::true($driver->deleteAsync('key'));
        Assert::true($driver->commitAsync());
    }

    public function testDeleteAsyncWithError(): void
    {
        $driver = $this->cache([
            'kv.Delete' => function () {
                throw new ServiceException('Error: Can not delete something');
            },
        ]);

        $driver->deleteAsync('key');
        Expect::exception(KeyValueException::class);
        $driver->commitAsync();
    }

    public function testDeleteMultipleAsync(): void
    {
        $driver = $this->cache(['kv.Delete' => $this->response([])]);
        Assert::true($driver->deleteMultipleAsync(['key', 'key2']));
        Assert::true($driver->commitAsync());
    }

    public function testDeleteMultipleAsyncWithError(): void
    {
        $driver = $this->cache([
            'kv.Delete' => function () {
                throw new ServiceException('Error: Can not delete something');
            },
        ]);

        $driver->deleteMultipleAsync(['key', 'key2']);
        Expect::exception(KeyValueException::class);
        $driver->commitAsync();
    }

    public function testSetAsyncMultipleWithInvalidKey(): void
    {
        Expect::exception(InvalidArgumentException::class)->withMessageContaining('Cache key must be a string, but int passed');

        $driver = $this->cache();
        $driver->setMultipleAsync([0 => 0xDEAD_BEEF]);
        // Make sure not reachable
        Assert::same(false, true);
        $driver->commitAsync();
    }

    public function testDeleteMultipleAsyncWithInvalidKey(): void
    {
        Expect::exception(InvalidArgumentException::class)->withMessageContaining('Cache key must be a string, but int passed');

        $driver = $this->cache();
        $driver->deleteMultipleAsync([0 => 0xDEAD_BEEF]);
        // Make sure not reachable
        Assert::same(false, true);
        $driver->commitAsync();
    }

    /**
     * @param array<string, mixed> $mapping
     */
    protected function cache(
        array $mapping = [],
        SerializerInterface $serializer = new DefaultSerializer(),
    ): AsyncCache {
        return new AsyncCache($this->asyncRPC($mapping), $this->name, $serializer);
    }

    /**
     * @param array<string, mixed> $mapping
     */
    protected function frozenDateCache(
        \DateTimeImmutable $date,
        array $mapping = [],
        SerializerInterface $serializer = new DefaultSerializer(),
    ): AsyncCache {
        return new AsyncFrozenDateCacheStub($date, $this->asyncRPC($mapping), $this->name, $serializer);
    }
}
