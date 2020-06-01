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
     * Create a shared cache storage.
     * @param string $storage
     * @return SharedCacheInterface
     */
    public function create(string $storage): SharedCacheInterface;
}
