<?php

/**
 * Spiral Framework, SpiralScout LLC.
 *
 * @author Valentin Vintsukevich (vvval)
 */

declare(strict_types=1);

namespace Spiral\KV;

interface SharedCacheInterface
{
    /**
     * Check if value exists.
     * @param string ...$keys
     * @return array
     * @throws SharedCacheException
     */
    public function has(string ...$keys): array;

    /**
     * Load content of multiple values.
     * @param string ...$keys
     * @return array
     * @throws SharedCacheException
     */
    public function get(string ...$keys): array;

    /**
     * Upload item to KV with TTL.  0 value in TTL means no TTL.
     * @param Item ...$items
     * @throws SharedCacheException
     */
    public function set(Item ...$items): void;

    /**
     * Set the TTL for multiply keys. Note that only TTL is used (the value is ignored)
     * @param Item ...$items
     * @throws SharedCacheException
     */
    public function expire(Item ...$items): void;

    /**
     * Return the rest time to live for provided keys. Not supported for the memcached and boltDB.
     * @param string ...$keys
     * @return array
     * @throws SharedCacheException
     */
    public function ttl(string ...$keys): array;

    /**
     * Delete one or multiple keys.
     * @param string ...$keys
     * @throws SharedCacheException
     */
    public function delete(string ...$keys): void;
}
