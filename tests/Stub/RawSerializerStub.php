<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\KeyValue\Tests\Stub;

use Spiral\RoadRunner\KeyValue\Serializer\SerializerInterface;

class RawSerializerStub implements SerializerInterface
{
    public function serialize($value): string
    {
        return $value;
    }

    public function unserialize(string $value): mixed
    {
        return $value;
    }
}
