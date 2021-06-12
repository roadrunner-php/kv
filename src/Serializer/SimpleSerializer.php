<?php

/**
 * This file is part of RoadRunner package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\RoadRunner\KeyValue\Serializer;

class SimpleSerializer implements SerializerInterface
{
    /**
     * {@inheritDoc}
     */
    public function serialize($value)
    {
        return \serialize($value);
    }

    /**
     * {@inheritDoc}
     */
    public function unserialize($value)
    {
        return \unserialize($value, [
            'allowed_classes' => true
        ]);
    }
}
