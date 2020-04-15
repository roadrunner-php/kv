<?php

declare(strict_types=1);

namespace Spiral\KV\Tests\Fixture;

use Spiral\Goridge\RPC as GoridgeRPC;
use Throwable;

class RPC extends GoridgeRPC
{
    /** @var mixed */
    private $return;

    /**
     * @inheritDoc
     * @throws Throwable
     */
    public function call(string $method, $payload, int $flags = 0)
    {
        if ($this->return instanceof Throwable) {
            throw $this->return;
        }

        return $this->return;
    }

    /**
     * @param array $return
     * @return static
     */
    public static function create($return = null): self
    {
        $rpc = new self(new Relay());
        $rpc->return = $return;

        return $rpc;
    }
}
