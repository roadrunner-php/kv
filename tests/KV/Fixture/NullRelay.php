<?php

declare(strict_types=1);

namespace Spiral\KV\Tests\Fixture;

use Spiral\Goridge\RelayInterface;

class NullRelay implements RelayInterface
{
    /**
     * @inheritDoc
     */
    public function send($payload, int $flags = null): ?int
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function receiveSync(int &$flags = null): ?int
    {
        return null;
    }

    public function sendPackage(string $headerPayload, ?int $headerFlags, string $bodyPayload, ?int $bodyFlags = null)
    {
    }
}
