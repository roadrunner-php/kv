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
use Spiral\RoadRunner\KeyValue\KeyNormalizer\KeyNormalizerInterface;
use Spiral\RoadRunner\KeyValue\KeyNormalizer\SimpleNormalizer;
use Spiral\RoadRunner\KeyValue\Serializer\SerializerInterface;
use Spiral\RoadRunner\KeyValue\Serializer\DefaultSerializer;

class Connection implements ConnectionInterface
{
    /**
     * @var RPCInterface
     */
    private RPCInterface $rpc;

    /**
     * @var SerializerInterface
     */
    private SerializerInterface $value;

    /**
     * @param RPCInterface $rpc
     * @param SerializerInterface|null $value
     */
    public function __construct(
        RPCInterface $rpc,
        SerializerInterface $value = null
    ) {
        $this->rpc = $rpc;
        $this->value = $value ?? new DefaultSerializer();
    }

    /**
     * @param SerializerInterface $serializer
     * @return $this
     */
    public function withSerializer(SerializerInterface $serializer): self
    {
        $self = clone $this;
        $self->value = $serializer;

        return $self;
    }

    /**
     * @return SerializerInterface
     */
    public function getSerializer(): SerializerInterface
    {
        return $this->value;
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
    public function create(string $name): TtlAwareCacheInterface
    {
        return new Cache($this->rpc, $name, $this->value);
    }
}
