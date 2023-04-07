<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\KeyValue\Serializer;

interface SerializerAwareInterface
{
    /**
     * @return $this
     */
    public function withSerializer(SerializerInterface $serializer): self;

    public function getSerializer(): SerializerInterface;
}
