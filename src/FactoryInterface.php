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
     * Create a shared cache driver.
     * @param string $driver
     * @return SharedCacheInterface
     */
    public function create(string $driver): SharedCacheInterface;
}
