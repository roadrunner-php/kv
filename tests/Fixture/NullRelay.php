<?php

declare(strict_types=1);

namespace Spiral\KV\Tests\Fixture;

use Spiral\Goridge\Frame;
use Spiral\Goridge\RelayInterface;

class NullRelay implements RelayInterface
{
    /**
     * {@inheritDoc}
     */
    public function send(Frame $frame): void
    {
    }

    /**
     * {@inheritDoc}
     */
    public function waitFrame(): Frame
    {
        return new Frame('');
    }
}
