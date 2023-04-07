<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\KeyValue;

use Spiral\Goridge\RPC\RPCInterface;
use Spiral\RoadRunner\KeyValue\Serializer\SerializerAwareTrait;
use Spiral\RoadRunner\KeyValue\Serializer\SerializerInterface;
use Spiral\RoadRunner\KeyValue\Serializer\DefaultSerializer;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class Factory implements FactoryInterface
{
    use SerializerAwareTrait;

    public function __construct(
        private readonly RPCInterface $rpc,
        SerializerInterface $serializer = new DefaultSerializer()
    ) {
        $this->setSerializer($serializer);
    }

    public function select(string $name): StorageInterface
    {
        return new Cache($this->rpc, $name, $this->getSerializer());
    }
}
