<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\KeyValue\Serializer;

trait SerializerAwareTrait
{
    protected SerializerInterface $serializer;

    protected function setSerializer(SerializerInterface $serializer): void
    {
        $this->serializer = $serializer;
    }

    /**
     * @return $this
     */
    public function withSerializer(SerializerInterface $serializer): self
    {
        $self = clone $this;
        $self->setSerializer($serializer);

        return $self;
    }

    public function getSerializer(): SerializerInterface
    {
        return $this->serializer;
    }
}
