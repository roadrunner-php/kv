<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\KeyValue;

use Spiral\RoadRunner\KeyValue\Serializer\SerializerAwareInterface;

interface StorageInterface extends
    TtlAwareCacheInterface,
    SerializerAwareInterface
{
    /**
     * @return non-empty-string
     */
    public function getName(): string;
}
