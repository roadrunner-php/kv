<?php

/**
 * This file is part of RoadRunner package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\RoadRunner\KeyValue\Tests\Stub;

use Spiral\RoadRunner\KeyValue\Serializer\SerializerInterface;

class RawSerializerStub implements SerializerInterface
{
    public function serialize($value): string
    {
        return $value;
    }

    public function unserialize(string $value)
    {
        return $value;
    }
}
