<?php

declare(strict_types=1);

namespace Spiral\KV;

use generated\Payload;
use Google\FlatBuffers\FlatbufferBuilder;

class Packer
{
    protected const BUFFER_SIZE = 1024;

    /**
     * @param string $storage
     * @param string ...$keys
     * @return Payload
     */
    public function packKeys(string $storage, string ...$keys): string
    {
        $builder = new FlatbufferBuilder(static::BUFFER_SIZE);

        $payload = Payload::createPayload(
            $builder,
            $builder->createString($storage),
            Payload::createItemsVector(
                $builder,
                array_map(
                    static function (string $key) use ($builder) {
                        return \generated\Item::createItem(
                            $builder,
                            $builder->createString($key),
                            $builder->createString(''),
                            $builder->createString('')
                        );
                    },
                    $keys
                )
            )
        );
        Payload::finishPayloadBuffer($builder, $payload);

        return $builder->sizedByteArray();
    }

    /**
     * @param string $storage
     * @param Item   ...$items
     * @return string
     */
    public function packItems(string $storage, Item ...$items): string
    {
        $builder = new FlatbufferBuilder(static::BUFFER_SIZE);

        $payload = Payload::createPayload(
            $builder,
            $builder->createString($storage),
            Payload::createItemsVector(
                $builder,
                array_map(
                    static function (Item $item) use ($builder) {
                        return \generated\Item::createItem(
                            $builder,
                            $builder->createString($item->getKey()),
                            $builder->createString($item->getValue()),
                            $builder->createString($item->getTTL())
                        );
                    },
                    $items
                )
            )
        );
        Payload::finishPayloadBuffer($builder, $payload);

        return $builder->sizedByteArray();
    }

    /**
     * @param string $storage
     * @param Item   ...$items
     * @return string
     */
    public function packItemsTTL(string $storage, Item ...$items): string
    {
        $builder = new FlatbufferBuilder(static::BUFFER_SIZE);

        $payload = Payload::createPayload(
            $builder,
            $builder->createString($storage),
            Payload::createItemsVector(
                $builder,
                array_map(
                    static function (Item $item) use ($builder) {
                        return \generated\Item::createItem(
                            $builder,
                            $builder->createString($item->getKey()),
                            $builder->createString(''),
                            $builder->createString($item->getTTL())
                        );
                    },
                    $items
                )
            )
        );
        Payload::finishPayloadBuffer($builder, $payload);

        return $builder->sizedByteArray();
    }
}
