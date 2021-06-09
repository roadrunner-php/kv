<?php

declare(strict_types=1);

namespace Spiral\KV\Tests\Fixture;

use Spiral\Goridge\RPC\RPC as GoridgeRPC;

class ExpectedResponseRPC extends GoridgeRPC
{
    /**
     * @var mixed
     */
    private $return;

    /**
     * @inheritDoc
     * @throws \Throwable
     */
    public function call(string $method, $payload, int $flags = 0)
    {
        if ($this->return instanceof Throwable) {
            throw $this->return;
        }

        return $this->return;
    }

    /**
     * @param mixed $return
     * @return static
     */
    public static function mock($return = null): self
    {
        $rpc = new self(new NullRelay());
        $rpc->return = $return;

        return $rpc;
    }
}
