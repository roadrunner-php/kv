<?php

/**
 * This file is part of RoadRunner package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\RoadRunner\KeyValue\Serializer;

use Spiral\RoadRunner\KeyValue\Exception\SerializationException;

class SodiumSerializer implements SerializerInterface
{
    /**
     * @var SerializerInterface
     */
    private SerializerInterface $serializer;

    /**
     * @var string
     */
    private string $key;

    /**
     * @param SerializerInterface $serializer
     *
     * @param string $key The key is used to decrypt and encrypt values;
     *                    The key must be generated using {@see sodium_crypto_box_keypair()}.
     */
    public function __construct(SerializerInterface $serializer, string $key)
    {
        $this->key = $key;
        $this->serializer = $serializer;

        if (!\function_exists('\\sodium_crypto_box_seal')) {
            throw new \LogicException('The "ext-sodium" PHP extension is not available');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function serialize($value): string
    {
        try {
            return \sodium_crypto_box_seal(
                $this->serializer->serialize($value),
                \sodium_crypto_box_publickey($this->key)
            );
        } catch (\SodiumException $e) {
            throw new SerializationException($e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function unserialize(string $value)
    {
        try {
            return $this->serializer->unserialize(
                \sodium_crypto_box_seal_open($value, $this->key)
            );
        } catch (\SodiumException $e) {
            throw new SerializationException($e->getMessage(), (int)$e->getCode(), $e);
        }
    }
}
