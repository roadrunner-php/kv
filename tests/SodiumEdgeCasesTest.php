<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\KeyValue\Tests;

use Testo\Test;
use Testo\Expect;
use Testo\Core\Exception\SkipTest;
use Spiral\RoadRunner\KeyValue\Exception\SerializationException;
use Spiral\RoadRunner\KeyValue\Serializer\DefaultSerializer;
use Spiral\RoadRunner\KeyValue\Serializer\SodiumSerializer;

#[Test]
final class SodiumEdgeCasesTest extends TestCase
{
    public function testSodiumSerializeInvalidKey(): void
    {
        $this->requireSodium();

        Expect::exception(SerializationException::class)->withMessageContaining('SODIUM_CRYPTO_BOX_KEYPAIRBYTES');

        $serializer = new SodiumSerializer(new DefaultSerializer(), 'KEY');

        $serializer->serialize('value');
    }

    public function testSodiumUnserializeInvalidKey(): void
    {
        $this->requireSodium();

        Expect::exception(SerializationException::class)->withMessageContaining('SODIUM_CRYPTO_BOX_KEYPAIRBYTES');

        $serializer = new SodiumSerializer(new DefaultSerializer(), 'KEY');

        $serializer->unserialize('value');
    }

    public function testSodiumNewKey(): void
    {
        $this->requireSodium();

        Expect::exception(SerializationException::class)->withMessageContaining('Can not decode the received data. Please make sure the encryption ' .
        'key matches the one used to encrypt this data');

        $serializer = new SodiumSerializer(new DefaultSerializer(), \sodium_crypto_box_keypair());
        $serializedValue = $serializer->serialize(\random_bytes(42));

        $serializer = new SodiumSerializer(new DefaultSerializer(), \sodium_crypto_box_keypair());
        $serializer->unserialize($serializedValue);
    }

    // Called from each test body, not a #[BeforeTest] hook: SkipTest thrown from a hook aborts the test.
    private function requireSodium(): void
    {
        if (!\extension_loaded('sodium')) {
            throw new SkipTest('ext-sodium is not loaded');
        }
    }
}
