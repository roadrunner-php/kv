<?php

/**
 * This file is part of RoadRunner package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\RoadRunner\KeyValue\Tests\Stub;

use Spiral\Goridge\RPC\Codec\JsonCodec;
use Spiral\Goridge\RPC\CodecInterface;
use Spiral\Goridge\RPC\Exception\RPCException;
use Spiral\Goridge\RPC\Exception\ServiceException;
use Spiral\Goridge\RPC\RPCInterface;

class RPCConnectionStub implements RPCInterface
{
    /**
     * @var CodecInterface
     */
    private CodecInterface $codec;

    /**
     * @var array<string, mixed>
     */
    private array $mapping;

    /**
     * @param array<string, mixed> $mapping
     */
    public function __construct(array $mapping = [])
    {
        $this->mapping = $mapping;
        $this->codec = new JsonCodec();
    }

    public function withServicePrefix(string $service): RPCInterface
    {
        throw new \LogicException(__METHOD__ . ' not implemented yet');
    }

    public function withCodec(CodecInterface $codec): RPCInterface
    {
        $self = clone $this;
        $self->codec = $codec;
        return $self;
    }

    /**
     * {@inheritDoc}
     */
    public function call(string $method, $payload, $options = null)
    {
        $result = $this->mapping[$method] ?? function () use ($method) {
            throw new ServiceException('RPC: can\'t find method ' . $method);
        };

        if ($result instanceof \Closure) {
            $result = $result($payload);
        }

        return $this->codec->decode($result, $options);
    }
}
