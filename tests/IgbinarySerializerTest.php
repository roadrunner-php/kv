<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\KeyValue\Tests;

use Spiral\RoadRunner\KeyValue\Serializer\IgbinarySerializer;
use Testo\Assert;
use Testo\Core\Exception\SkipTest;
use Testo\Data\DataSet;
use Testo\Test;

#[Test]
final class IgbinarySerializerTest
{
    #[DataSet([null], 'null')]
    #[DataSet([false], 'false')]
    #[DataSet([0], 'zero')]
    #[DataSet(['value'], 'string')]
    #[DataSet([['key' => 'value', 42]], 'array')]
    public function testRoundTrip(mixed $value): void
    {
        if (!\extension_loaded('igbinary')) {
            throw new SkipTest('ext-igbinary is not loaded');
        }

        $serializer = new IgbinarySerializer();

        Assert::same($serializer->unserialize($serializer->serialize($value)), $value);
    }
}
