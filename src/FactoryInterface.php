<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\KeyValue;

use Spiral\RoadRunner\KeyValue\Serializer\SerializerAwareInterface;

interface FactoryInterface extends SerializerAwareInterface
{
    /**
     * Create a shared cache storage by its name.
     *
     * @param non-empty-string $name
     */
    public function select(string $name): StorageInterface;
}
