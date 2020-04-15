<?php

declare(strict_types=1);

namespace Spiral\KV\Tests\Fixture;

use Spiral\Goridge\RPC as GoridgeRPC;

class RPC extends GoridgeRPC
{
    /**
     * @inheritDoc
     */
    public function call(string $method, $payload, int $flags = 0)
    {
        return func_get_args();
    }

    /**
     * @return static
     */
    public static function create(): self
    {
        return new self(new Relay());
    }
}
