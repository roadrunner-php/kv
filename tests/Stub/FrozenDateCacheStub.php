<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\KeyValue\Tests\Stub;

use Spiral\Goridge\RPC\RPCInterface;
use Spiral\RoadRunner\KeyValue\Cache;
use Spiral\RoadRunner\KeyValue\Serializer\DefaultSerializer;
use Spiral\RoadRunner\KeyValue\Serializer\SerializerInterface;

final class FrozenDateCacheStub extends Cache
{
    private \DateTimeImmutable $date;

    public function __construct(
        \DateTimeImmutable $date,
        RPCInterface $rpc,
        string $name,
        SerializerInterface $serializer = new DefaultSerializer()
    ) {
        $this->date = $date;

        parent::__construct($rpc, $name, $serializer);
    }

    final protected function now(): \DateTimeImmutable
    {
        return $this->date;
    }
}
