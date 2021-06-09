<?php

/**
 * Spiral Framework, SpiralScout LLC.
 *
 * @author Valentin Vintsukevich (vvval)
 */

declare(strict_types=1);

namespace Spiral\KV;

interface FactoryInterface
{
    /**
     * @return bool
     */
    public function isAvailable(): bool;

    /**
     * Create a shared cache storage.
     *
     * @param string $storage
     * @return CacheInterface
     */
    public function create(string $storage): CacheInterface;
}
