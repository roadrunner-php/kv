<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\KeyValue\Tests;

use Spiral\RoadRunner\KeyValue\Exception\SerializationException;
use Spiral\RoadRunner\KeyValue\Serializer\DefaultSerializer;
use Spiral\RoadRunner\KeyValue\Serializer\SodiumSerializer;

/**
 * @requires extension sodium
 */
class SodiumEdgeCasesTestCase extends TestCase
{
    public function testSodiumSerializeInvalidKey(): void
    {
        $this->expectException(SerializationException::class);
        $this->expectExceptionMessage('SODIUM_CRYPTO_BOX_KEYPAIRBYTES');

        $serializer = new SodiumSerializer(new DefaultSerializer(), 'KEY');

        $serializer->serialize('value');
    }

    public function testSodiumUnserializeInvalidKey(): void
    {
        $this->expectException(SerializationException::class);
        $this->expectExceptionMessage('SODIUM_CRYPTO_BOX_KEYPAIRBYTES');

        $serializer = new SodiumSerializer(new DefaultSerializer(), 'KEY');

        $serializer->unserialize('value');
    }

    public function testSodiumNewKey(): void
    {
        $this->expectException(SerializationException::class);
        $this->expectExceptionMessage(
            'Can not decode the received data. Please make sure the encryption ' .
            'key matches the one used to encrypt this data'
        );

        $serializer = new SodiumSerializer(new DefaultSerializer(), \sodium_crypto_box_keypair());
        $serializedValue = $serializer->serialize(\random_bytes(42));

        $serializer = new SodiumSerializer(new DefaultSerializer(), \sodium_crypto_box_keypair());
        $serializer->unserialize($serializedValue);
    }
}
