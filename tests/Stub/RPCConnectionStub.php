<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\KeyValue\Tests\Stub;

use Spiral\Goridge\RPC\Codec\JsonCodec;
use Spiral\Goridge\RPC\CodecInterface;
use Spiral\Goridge\RPC\Exception\RPCException;
use Spiral\Goridge\RPC\Exception\ServiceException;
use Spiral\Goridge\RPC\RPCInterface;

class RPCConnectionStub implements RPCInterface
{
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
     * @param non-empty-string $method
     */
    public function call(string $method, mixed $payload, mixed $options = null): mixed
    {
        $result = $this->mapping[$method] ?? static function () use ($method) {
            throw new ServiceException('RPC: can\'t find method ' . $method);
        };

        if ($result instanceof \Closure) {
            $result = $result($payload);
        }

        return $this->codec->decode($result, $options);
    }
}
