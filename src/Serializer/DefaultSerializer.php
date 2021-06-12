<?php

/**
 * This file is part of RoadRunner package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\RoadRunner\KeyValue\Serializer;

class DefaultSerializer implements SerializerInterface
{
    /**
     * {@inheritDoc}
     */
    public function serialize($value): string
    {
        return \serialize($value);
    }

    /**
     * {@inheritDoc}
     */
    public function unserialize(string $value)
    {
        switch($value) {
            case 'N;':
                return null;

            case 'b:0;':
                return false;

            case 'b:1;':
                return true;
        }

        return \unserialize($value, [
            'allowed_classes' => true
        ]);
    }
}
