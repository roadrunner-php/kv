<?php

/**
 * This file is part of RoadRunner package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\RoadRunner\KeyValue;

interface FactoryInterface
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
    public function select(string $name): TtlAwareCacheInterface;
}
