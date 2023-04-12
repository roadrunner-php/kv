<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\KeyValue\Tests;

use Spiral\RoadRunner\KeyValue\Factory;
use Spiral\RoadRunner\KeyValue\FactoryInterface;
use Spiral\RoadRunner\KeyValue\Serializer\DefaultSerializer;
use Spiral\RoadRunner\KeyValue\Serializer\SerializerInterface;

class FactoryTest extends TestCase
{
    /**
     * @param array<string, mixed> $mapping
     */
    private function factory(array $mapping = [], SerializerInterface $serializer = new DefaultSerializer()): FactoryInterface
    {
        return new Factory($this->rpc($mapping), $serializer);
    }

    public function testFactoryCreation(): void
    {
        $this->expectNotToPerformAssertions();
        $this->factory();
    }

    public function testSuccessSelectOfUnknownStorage(): void
    {
        $name = \random_bytes(32);

        $driver = $this->factory()
            ->select($name)
        ;

        $this->assertSame($name, $driver->getName());
    }
}
