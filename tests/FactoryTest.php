<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\KeyValue\Tests;

use Testo\Test;
use Testo\Assert\ExpectNoAssertions;
use Testo\Assert;
use Spiral\RoadRunner\KeyValue\AsyncCache;
use Spiral\RoadRunner\KeyValue\Cache;
use Spiral\RoadRunner\KeyValue\Factory;
use Spiral\RoadRunner\KeyValue\FactoryInterface;
use Spiral\RoadRunner\KeyValue\Serializer\DefaultSerializer;
use Spiral\RoadRunner\KeyValue\Serializer\SerializerInterface;
use Spiral\RoadRunner\KeyValue\Tests\Stub\RawSerializerStub;

#[Test]
final class FactoryTest extends TestCase
{
    #[ExpectNoAssertions]
    public function testFactoryCreation(): void
    {
        $this->factory();
    }

    #[ExpectNoAssertions]
    public function testAsyncFactoryCreation(): void
    {
        $this->asyncFactory();
    }

    public function testSuccessSelectOfUnknownStorage(): void
    {
        $name = \random_bytes(32);

        $driver = $this->factory()->select($name);

        Assert::same($driver->getName(), $name);
    }

    public function testSuccessSelectOfUnknownStorageWithAsync(): void
    {
        $name = \random_bytes(32);

        $driver = $this->asyncFactory()->select($name);

        Assert::same($driver->getName(), $name);
    }

    public function testSelectReturnsCacheForSyncRpc(): void
    {
        $driver = $this->factory()->select('name');

        Assert::same($driver::class, Cache::class);
    }

    public function testSelectReturnsAsyncCacheForAsyncRpc(): void
    {
        $driver = $this->asyncFactory()->select('name');

        Assert::instanceOf($driver, AsyncCache::class);
    }

    public function testSelectedStorageUsesFactorySerializer(): void
    {
        $serializer = new RawSerializerStub();

        Assert::same($this->factory(serializer: $serializer)->select('name')->getSerializer(), $serializer);
        Assert::same($this->asyncFactory(serializer: $serializer)->select('name')->getSerializer(), $serializer);
    }

    public function testWithSerializerDoesNotChangeTheOriginal(): void
    {
        $serializer = new RawSerializerStub();
        $factory = $this->factory();

        $decorated = $factory->withSerializer($serializer);

        Assert::same($decorated->select('name')->getSerializer(), $serializer);
        Assert::instanceOf($factory->select('name')->getSerializer(), DefaultSerializer::class);
    }

    /**
     * @param array<string, mixed> $mapping
     */
    private function factory(
        array $mapping = [],
        SerializerInterface $serializer = new DefaultSerializer(),
    ): FactoryInterface {
        return new Factory($this->rpc($mapping), $serializer);
    }

    /**
     * @param array<string, mixed> $mapping
     */
    private function asyncFactory(
        array $mapping = [],
        SerializerInterface $serializer = new DefaultSerializer(),
    ): FactoryInterface {
        return new Factory($this->asyncRPC($mapping), $serializer);
    }
}
