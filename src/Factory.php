<?php

/**
 * This file is part of RoadRunner package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\KV;

use Spiral\Goridge\RPC\RPCInterface;
use Spiral\KV\Internal\Packer;

class Factory implements FactoryInterface
{
    /**
     * @var RPCInterface
     */
    private RPCInterface $rpc;

    /**
     * @param RPCInterface $rpc
     */
    public function __construct(RPCInterface $rpc)
    {
        $this->rpc = $rpc;
    }

    public function isAvailable(): bool
    {
        dd($this->rpc->call('informer.List', ''));
    }

    /**
     * {@inheritDoc}
     */
    public function create(string $storage): CacheInterface
    {
        return new Cache($this->rpc, new Packer(), $storage);
    }
}
