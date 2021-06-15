<?php

/**
 * This file is part of RoadRunner package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\RoadRunner\KeyValue\Tests\Stub;

use Spiral\Goridge\RPC\RPCInterface;
use Spiral\RoadRunner\KeyValue\Cache;
use Spiral\RoadRunner\KeyValue\Serializer\SerializerInterface;

final class FrozenDateCacheStub extends Cache
{
    private \DateTimeImmutable $date;

    public function __construct(
        \DateTimeImmutable $date,
        RPCInterface $rpc,
        string $name,
        SerializerInterface $serializer = null
    ) {
        $this->date = $date;

        parent::__construct($rpc, $name, $serializer);
    }

    final protected function now(): \DateTimeImmutable
    {
        return $this->date;
    }
}
