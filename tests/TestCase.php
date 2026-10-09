<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\KeyValue\Tests;

use Spiral\RoadRunner\KeyValue\Tests\Stub\AsyncRPCConnectionStub;
use Spiral\RoadRunner\KeyValue\Tests\Stub\RPCConnectionStub;

abstract class TestCase
{
    public static function valuesDataProvider(): array
    {
        return [
            'null' => [null],
            'int' => [0xDEAD_BEEF],
            'zero int' => [0],
        ];
    }

    /**
     * @param array<string, mixed> $mapping
     */
    protected function rpc(array $mapping = []): RPCConnectionStub
    {
        return new RPCConnectionStub($mapping);
    }

    /**
     * @param array<string, mixed> $mapping
     */
    protected function asyncRPC(array $mapping = []): AsyncRPCConnectionStub
    {
        return new AsyncRPCConnectionStub($mapping);
    }
}
