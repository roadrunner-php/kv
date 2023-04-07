<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\KeyValue\Serializer;

use Spiral\RoadRunner\KeyValue\Exception\SerializationException;

final class DefaultSerializer implements SerializerInterface
{
    public function serialize(mixed $value): string
    {
        return \serialize($value);
    }

    public function unserialize(string $value): mixed
    {
        // Deserialization optimizations
        // @codeCoverageIgnoreStart
        switch ($value) {
            case 'N;':
                return null;

            case 'b:0;':
                return false;

            case 'b:1;':
                return true;
        }
        // @codeCoverageIgnoreEnd

        \error_clear_last();

        /** @var mixed $result */
        $result = @\unserialize($value, [
            'allowed_classes' => true,
        ]);

        if (($err = \error_get_last()) !== null) {
            $exception = new \ErrorException($err['message'], 0, $err['type'], $err['file'], $err['line']);
            throw new SerializationException($err['message'], $err['type'], $exception);
        }

        return $result;
    }
}
