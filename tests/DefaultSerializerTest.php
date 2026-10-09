<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\KeyValue\Tests;

use Spiral\RoadRunner\KeyValue\Exception\SerializationException;
use Spiral\RoadRunner\KeyValue\Serializer\DefaultSerializer;
use Testo\Assert;
use Testo\Data\DataSet;
use Testo\Expect;
use Testo\Test;

#[Test]
final class DefaultSerializerTest
{
    #[DataSet([null], 'null')]
    #[DataSet([false], 'false')]
    #[DataSet([true], 'true')]
    #[DataSet([0], 'zero')]
    #[DataSet([4.2], 'float')]
    #[DataSet(['value'], 'string')]
    #[DataSet([['key' => 'value', 42]], 'array')]
    public function testRoundTrip(mixed $value): void
    {
        $serializer = new DefaultSerializer();

        Assert::same($serializer->unserialize($serializer->serialize($value)), $value);
    }

    public function testRoundTripOfObject(): void
    {
        $serializer = new DefaultSerializer();
        $value = new \ArrayObject(['key' => 'value']);

        Assert::equals($serializer->unserialize($serializer->serialize($value)), $value);
    }

    public function testUnserializeInvalidData(): void
    {
        Expect::exception(SerializationException::class);

        (new DefaultSerializer())->unserialize('invalid');
    }
}
