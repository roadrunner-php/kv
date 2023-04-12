<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\KeyValue\Serializer;

use Spiral\RoadRunner\KeyValue\Exception\SerializationException;

final class SodiumSerializer implements SerializerInterface
{
    /**
     * @param string $key The key is used to decrypt and encrypt values;
     *                    The key must be generated using {@see sodium_crypto_box_keypair()}.
     */
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly string $key
    ) {
        $this->assertAvailable();
    }

    /**
     * @codeCoverageIgnore Reason: Ignore environment-aware assertions
     */
    private function assertAvailable(): void
    {
        if (! \function_exists('\\sodium_crypto_box_seal')) {
            throw new \LogicException('The "ext-sodium" PHP extension is not available');
        }
    }

    public function serialize(mixed $value): string
    {
        try {
            return \sodium_crypto_box_seal(
                $this->serializer->serialize($value),
                \sodium_crypto_box_publickey($this->key)
            );
        } catch (\SodiumException $e) {
            throw new SerializationException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function unserialize(string $value): mixed
    {
        try {
            $result = \sodium_crypto_box_seal_open($value, $this->key);

            if ($result === false) {
                throw new SerializationException(
                    'Can not decode the received data. Please make sure '.
                    'the encryption key matches the one used to encrypt this data'
                );
            }

            return $this->serializer->unserialize($result);
        } catch (\SodiumException $e) {
            throw new SerializationException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
