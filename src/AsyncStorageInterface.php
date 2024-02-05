<?php

namespace Spiral\RoadRunner\KeyValue;

use DateInterval;
use Spiral\RoadRunner\KeyValue\Exception\KeyValueException;

interface AsyncStorageInterface extends StorageInterface
{
    /**
     * @throws KeyValueException
     */
    public function commitAsync(): bool;

    /**
     * @psalm-param iterable<string, mixed> $values
     * @psalm-param positive-int|DateInterval|null $ttl
     * @throws KeyValueException
     */
    public function setMultipleAsync(iterable $values, null|int|DateInterval $ttl = null): bool;

    /**
     * @psalm-param positive-int|DateInterval|null $ttl
     * @throws KeyValueException
     */
    public function setAsync(string $key, mixed $value, null|int|DateInterval $ttl = null): bool;
}
