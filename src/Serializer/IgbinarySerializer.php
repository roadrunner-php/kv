<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\KeyValue\Serializer;

use Spiral\RoadRunner\KeyValue\Exception\SerializationException;

final class IgbinarySerializer implements SerializerInterface
{
    private const SUPPORTED_VERSION_MIN = '3.1.6';
    private const ERROR_NOT_AVAILABLE =
        'The "ext-igbinary" PHP extension is not available';
    private const ERROR_NON_COMPATIBLE =
        'Current version of the "ext-igbinary" PHP extension (v%s) does not meet the requirements, ' .
        'version v' . self::SUPPORTED_VERSION_MIN . ' or higher required';

    /**
     * @codeCoverageIgnore Reason: Contains only initialization assertion
     * @throws \LogicException
     */
    public function __construct()
    {
        $this->assertAvailable();
    }

    #[\Override]
    public function serialize(mixed $value): string
    {
        $result = \igbinary_serialize($value);

        if ($result === false) {
            throw new SerializationException('Can not serialize the value using ext-igbinary');
        }

        return $result;
    }

    #[\Override]
    public function unserialize(string $value): mixed
    {
        return \igbinary_unserialize($value);
    }

    /**
     * @codeCoverageIgnore Reason: Ignore environment-aware assertions
     */
    private function assertAvailable(): void
    {
        if (! \extension_loaded('igbinary')) {
            throw new \LogicException(self::ERROR_NOT_AVAILABLE);
        }

        $version = (string) \phpversion('igbinary');

        if (\version_compare(self::SUPPORTED_VERSION_MIN, $version, '>')) {
            throw new \LogicException(\sprintf(self::ERROR_NON_COMPATIBLE, $version));
        }
    }
}
