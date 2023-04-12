<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\KeyValue;

use Psr\SimpleCache\CacheInterface;
use Spiral\RoadRunner\KeyValue\Exception\InvalidArgumentException;

interface TtlAwareCacheInterface extends CacheInterface
{
    /**
     * @param non-empty-string $key

     * @throws InvalidArgumentException
     */
    public function getTtl(string $key): ?\DateTimeInterface;

    /**
     * @param iterable<non-empty-string> $keys
     * @return iterable<non-empty-string, \DateTimeInterface|null>
     *
     * @throws InvalidArgumentException
     */
    public function getMultipleTtl(iterable $keys = []): iterable;
}
