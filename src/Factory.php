<?php

/**
 * Spiral Framework, SpiralScout LLC.
 *
 * @author Valentin Vintsukevich (vvval)
 */

declare(strict_types=1);

namespace Spiral\KV;

use Spiral\Goridge\RPC;

class Factory implements FactoryInterface
{
    /** @var RPC */
    private $rpc;

    /**
     * @param RPC $rpc
     */
    public function __construct(RPC $rpc)
    {
        $this->rpc = $rpc;
    }

    /**
     * @inheritDoc
     */
    public function create(string $driver): SharedCacheInterface
    {
        return new SharedCache($this->rpc, $driver);
    }
}
