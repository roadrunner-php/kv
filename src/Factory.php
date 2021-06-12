<?php

/**
 * This file is part of RoadRunner package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\RoadRunner\KeyValue;

use Spiral\Goridge\RPC\Codec\JsonCodec;
use Spiral\Goridge\RPC\RPCInterface;
use Spiral\RoadRunner\KeyValue\Serializer\SerializerAwareTrait;
use Spiral\RoadRunner\KeyValue\Serializer\SerializerInterface;
use Spiral\RoadRunner\KeyValue\Serializer\DefaultSerializer;

final class Factory implements FactoryInterface
{
    use SerializerAwareTrait;

    /**
     * @var RPCInterface
     */
    private RPCInterface $rpc;


    /**
     * @param RPCInterface $rpc
     * @param SerializerInterface|null $serializer
     */
    public function __construct(RPCInterface $rpc, SerializerInterface $serializer = null)
    {
        $this->rpc = $rpc;
        $this->setSerializer($serializer ?? new DefaultSerializer());
    }

    /**
     * {@inheritDoc}
     */
    public function isAvailable(): bool
    {
        try {
            /** @var array<string>|mixed $result */
            $result = $this->rpc
                ->withCodec(new JsonCodec())
                ->call('informer.List', true);

            if (! \is_array($result)) {
                return false;
            }

            return \in_array('kv', $result, true);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function select(string $name): TtlAwareCacheInterface
    {
        return new Cache($this->rpc, $name, $this->getSerializer());
    }
}
