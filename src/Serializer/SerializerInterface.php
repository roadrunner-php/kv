<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\KeyValue\Serializer;

use Spiral\RoadRunner\KeyValue\Exception\SerializationException;

interface SerializerInterface
{
    /**
     * @throws SerializationException
     */
    public function serialize(mixed $value): string;

    /**
     * @throws SerializationException
     */
    public function unserialize(string $value): mixed;
}
