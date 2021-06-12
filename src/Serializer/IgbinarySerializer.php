<?php

/**
 * This file is part of RoadRunner package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\RoadRunner\KeyValue\Serializer;

class IgbinarySerializer implements SerializerInterface
{
    /**
     * @var string
     */
    private const SUPPORTED_VERSION_MIN = '3.1.6';

    /**
     * @var string
     */
    private const ERROR_NOT_AVAILABLE =
        'The "ext-igbinary" PHP extension is not available';

    /**
     * @var string
     */
    private const ERROR_NON_COMPATIBLE =
        'Current version of the "ext-igbinary" PHP extension (v%s) does not meet the requirements, ' .
        'version v' . self::SUPPORTED_VERSION_MIN . ' or higher required';

    /**
     * @throws \LogicException
     */
    public function __construct()
    {
        if (! \extension_loaded('igbinary')) {
            throw new \LogicException(self::ERROR_NOT_AVAILABLE);
        }

        if (\version_compare(self::SUPPORTED_VERSION_MIN, \phpversion('igbinary'), '>')) {
            throw new \LogicException(\sprintf(self::ERROR_NON_COMPATIBLE, \phpversion('igbinary')));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function serialize($value): string
    {
        return \igbinary_serialize($value);
    }

    /**
     * {@inheritDoc}
     */
    public function unserialize(string $value)
    {
        return \igbinary_unserialize($value);
    }
}
