<?php

declare(strict_types=1);

namespace Spiral\KV\Tests\Fixture;

use Spiral\Goridge\RelayInterface;

class Relay implements RelayInterface
{
    /**
     * @inheritDoc
     */
    public function send($payload, int $flags = null)
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function receiveSync(int &$flags = null)
    {
        return null;
    }
}
