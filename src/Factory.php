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

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
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
     * @deprecated Information about RoadRunner plugins is not available since RoadRunner version 2.2
     */
    public function isAvailable(): bool
    {
        throw new \RuntimeException(\sprintf('%s::isAvailable method is deprecated.', self::class));
    }

    /**
     * {@inheritDoc}
     */
    public function select(string $name): StorageInterface
    {
        return new Cache($this->rpc, $name, $this->getSerializer());
    }
}
