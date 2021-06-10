<?php

/**
 * Spiral Framework, SpiralScout LLC.
 *
 * @author Valentin Vintsukevich (vvval)
 */

declare(strict_types=1);

namespace Spiral\RoadRunner\KeyValue;

interface ConnectionInterface
{
    /**
     * Returns information about whether a key value plugin is available.
     *
     * @return bool
     */
    public function isAvailable(): bool;

    /**
     * Create a shared cache storage by its name.
     *
     * @param string $name
     * @return TtlAwareCacheInterface
     */
    public function create(string $name): TtlAwareCacheInterface;
}
